
let lastVisit = {};

function isDuplicate(visit) {
  const key = visit.url;
  const now = Date.now();
  if (lastVisit[key] && now - lastVisit[key] < 5000) {
    return true;
  }
  lastVisit[key] = now;
  return false;
}

function sendVisit(visit) {
  if (isDuplicate(visit)) return;

  fetch("https://segfault.es/logger.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(visit)
  })
  .then(response => {
    if (!response.ok) throw new Error("Falló el envío");
  })
  .catch(() => {
    chrome.storage.local.get({ failedLogs: [] }, (data) => {
      data.failedLogs.push(visit);
      chrome.storage.local.set({ failedLogs: data.failedLogs.slice(-100) });
    });
  });
}

setInterval(() => {
  chrome.storage.local.get({ failedLogs: [] }, (data) => {
    if (data.failedLogs.length === 0) return;
    const pending = [...data.failedLogs];
    const sent = [];

    pending.forEach((visit) => {
      fetch("https://segfault.es/logger.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(visit)
      })
      .then(response => {
        if (response.ok) {
          sent.push(visit);
        }
      })
      .finally(() => {
        const remaining = pending.filter(v => !sent.includes(v));
        chrome.storage.local.set({ failedLogs: remaining });
      });
    });
  });
}, 15000);

chrome.runtime.onInstalled.addListener(() => {
  chrome.storage.local.get(["browserId"], (data) => {
    if (!data.browserId) {
      const uuid = crypto.randomUUID();
      chrome.storage.local.set({ browserId: uuid });
    }
  });
});

function logVisit(details, fuente, tabOverride = null) {
  const tabId = details.tabId || (tabOverride && tabOverride.id);
  if (!tabId) return;

  chrome.tabs.get(tabId, (tab) => {
    if (!tab || !tab.url.startsWith("http")) return;
    chrome.storage.local.get(["browserId"], (data) => {
      const visit = {
        timestamp: new Date().toISOString(),
        url: tab.url,
        title: tab.title || '',
        tabId: tabId,
        userId: "Bob",
        browserId: data.browserId || "unknown",
        fuente: fuente
      };
      sendVisit(visit);
    });
  });
}

chrome.webNavigation.onCompleted.addListener((details) => {
  if (details.frameId !== 0) return;
  logVisit(details, "onCompleted");
}, { url: [{ schemes: ["http", "https"] }] });

chrome.webNavigation.onHistoryStateUpdated.addListener((details) => {
  logVisit(details, "onHistoryStateUpdated");
}, { url: [{ schemes: ["http", "https"] }] });

chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.url && tab.url.startsWith("http")) {
    logVisit({ tabId: tabId }, "onUpdated", tab);
  }
});
