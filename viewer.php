<?php
date_default_timezone_set("Europe/Madrid");
$logfile = "/home/data/www/logger.log";
$lines = file_exists($logfile) ? file($logfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$logs = [];

foreach ($lines as $line) {
    $data = json_decode($line, true);
    if (is_array($data) && isset($data["timestamp"])) {
        try {
            $utc = new DateTime($data["timestamp"], new DateTimeZone("UTC"));
            $local = $utc->setTimezone(new DateTimeZone("Europe/Madrid"));
            $data["local_time"] = $local->format("Y-m-d H:i:s");
            $data["parsed_time"] = $local->getTimestamp();
        } catch (Exception $e) {
            $data["local_time"] = $data["timestamp"];
            $data["parsed_time"] = strtotime($data["timestamp"]);
        }

        $data["domain"] = parse_url($data["url"] ?? '', PHP_URL_HOST) ?? '';
        $logs[] = $data;
    }
}

usort($logs, fn($a, $b) => $b["parsed_time"] <=> $a["parsed_time"]);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Security Monitor Viewer</title>
<style>
    body { font-family: sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; table-layout: fixed; }
    th, td { padding: 8px 10px; border: 1px solid #ccc; vertical-align: top; }

    th { background-color: #f2f2f2; text-align: left; }

    th:nth-child(1), td:nth-child(1) { width: 140px; }  /* Hora */
    th:nth-child(2), td:nth-child(2) { width: 80px; }   /* Usuario (reducido) */
    th:nth-child(3), td:nth-child(3) { width: 180px; }  /* Dominio (ampliado) */
    th:nth-child(5), td:nth-child(5) { width: 160px; }  /* Browser ID */
    th:nth-child(6), td:nth-child(6) { width: 80px; }   /* Fuente */

    td.title-col {
        min-width: 400px;
        word-wrap: break-word;
        white-space: normal;
    }

    input, button { padding: 6px; margin-right: 10px; }
    .filters { margin-bottom: 10px; }
    .url-link { float: right; font-size: 0.9em; }
    .timestamp { white-space: nowrap; }
</style>

    <script>
        let logs = <?php echo json_encode($logs); ?>;

        function renderTable() {
            const tbody = document.getElementById("log-body");
            tbody.innerHTML = "";
            const userF = document.getElementById("filter-user").value.toLowerCase();
            const domainF = document.getElementById("filter-domain").value.toLowerCase();
            const searchF = document.getElementById("filter-search").value.toLowerCase();
            let filtered = [];

            logs.forEach(log => {
                const matchesUser = !userF || (log.userId || "").toLowerCase().includes(userF);
                const matchesDomain = !domainF || (log.domain || "").toLowerCase().includes(domainF);
                const content = (log.title || "") + " " + (log.url || "");
                const matchesSearch = !searchF || content.toLowerCase().includes(searchF);

                if (matchesUser && matchesDomain && matchesSearch) {
                    filtered.push(log);
                    const row = document.createElement("tr");
                    const displayTitle = log.title?.trim() || log.url;
                    const urlLink = `<a href="${log.url}" target="_blank" class="url-link">[ver]</a>`;
                    row.innerHTML = `
                        <td class="timestamp">${log.local_time || log.timestamp}</td>
                        <td>${log.userId || ""}</td>
                        <td>${log.domain || ""}</td>
                        <td class="title-col">${displayTitle}${urlLink}</td>
                        <td>${log.browserId || ""}</td>
                        <td>${log.fuente || ""}</td>
                    `;
                    tbody.appendChild(row);
                }
            });

            window.currentFilteredLogs = filtered;
        }

        function clearFilters() {
            document.getElementById("filter-user").value = "";
            document.getElementById("filter-domain").value = "";
            document.getElementById("filter-search").value = "";
            renderTable();
        }

        function exportCSV() {
            if (!window.currentFilteredLogs || window.currentFilteredLogs.length === 0) {
                alert("No hay datos para exportar.");
                return;
            }

            const rows = [
                ["Hora", "Usuario", "Dominio", "Título", "URL", "Browser ID", "Fuente"]
            ];

            window.currentFilteredLogs.forEach(log => {
                rows.push([
                    log.local_time || log.timestamp,
                    log.userId || "",
                    log.domain || "",
                    (log.title || "").replace(/[\n\r]/g, " "),
                    log.url || "",
                    log.browserId || "",
                    log.fuente || ""
                ]);
            });

            const csv = rows.map(r => r.map(field => `"${field.replace(/"/g, '""')}"`).join(",")).join("\n");
            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "security_logs.csv";
            a.click();
            URL.revokeObjectURL(url);
        }

        function autoRefresh() {
            fetch(location.href)
              .then(resp => resp.text())
              .then(html => {
                  const match = html.match(/logs = (.+?);/);
                  if (match) {
                      logs = JSON.parse(match[1]);
                      renderTable();
                  }
              });
        }

        window.onload = () => {
            renderTable();
            setInterval(autoRefresh, 15000);
        };
    </script>
</head>
<body>
    <h2>Security Monitor - Visor</h2>

    <div class="filters">
        <label>Usuario: <input type="text" id="filter-user" placeholder="e.g. celiav" oninput="renderTable()"></label>
        <label>Dominio: <input type="text" id="filter-domain" placeholder="youtube.com" oninput="renderTable()"></label>
        <label>Buscar: <input type="text" id="filter-search" placeholder="título o URL" oninput="renderTable()"></label>
        <button onclick="clearFilters()">Limpiar filtros</button>
        <button onclick="exportCSV()">Exportar CSV</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Hora</th>
                <th>Usuario</th>
                <th>Dominio</th>
                <th>Título</th>
                <th>Browser ID</th>
                <th>Fuente</th>
            </tr>
        </thead>
        <tbody id="log-body"></tbody>
    </table>
</body>
</html>
