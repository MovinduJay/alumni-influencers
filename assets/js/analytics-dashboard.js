(function () {
    'use strict';

    var root = document.getElementById('analytics-dashboard');
    if (!root || typeof Chart === 'undefined') {
        return;
    }

    var apiBase = root.getAttribute('data-api-base').replace(/\/$/, '');
    var token = root.getAttribute('data-token');
    var charts = {};
    var currentAnalytics = null;
    var chartTitles = {
        programmeChart: 'Alumni by Programme',
        industryChart: 'Industry Sector Distribution',
        graduationChart: 'Graduation Year Trend',
        skillChart: 'Top Skills in Demand',
        gapChart: 'Curriculum Gap Severity',
        developmentChart: 'Professional Development Sources',
        matrixChart: 'Programme to Sector Pathways',
        biddingChart: 'Featured Placement Outcomes'
    };
    var palette = ['#2563eb', '#16a34a', '#0891b2', '#dc2626', '#7c3aed', '#0b4f4a', '#db2777', '#475569'];
    var gapColors = { critical: '#dc2626', significant: '#0891b2', emerging: '#2563eb', covered: '#16a34a', missing: '#dc2626', partial: '#64748b' };

    var form = document.getElementById('analytics-filters');
    var loading = document.getElementById('loading-state');
    var error = document.getElementById('error-state');
    var savePresetModalElement = document.getElementById('save-preset-modal');
    var savePresetForm = document.getElementById('save-preset-form');
    var presetNameInput = document.getElementById('preset-name');
    var savePresetModal = savePresetModalElement && window.bootstrap ? new window.bootstrap.Modal(savePresetModalElement) : null;
    var noDataPlugin = {
        id: 'noDataMessage',
        afterDraw: function (chart) {
            if (!chart.options.plugins.noDataMessage || !chart.options.plugins.noDataMessage.display) {
                return;
            }

            var ctx = chart.ctx;
            var area = chart.chartArea;
            ctx.save();
            ctx.fillStyle = '#64748b';
            ctx.font = '600 15px Inter, system-ui, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('No results for the selected filters', (area.left + area.right) / 2, (area.top + area.bottom) / 2);
            ctx.restore();
        }
    };

    Chart.register(noDataPlugin);

    function params() {
        var data = new FormData(form);
        var query = new URLSearchParams();
        data.forEach(function (value, key) {
            if (value !== '' && key.indexOf('metric_') !== 0) {
                query.set(key, value);
            }
        });
        return query;
    }

    function fetchJson(path, query) {
        var url = apiBase + path + (query && query.toString() ? '?' + query.toString() : '');
        return fetch(url, {
            headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
            credentials: 'same-origin'
        }).then(function (response) {
            return response.text().then(function (text) {
                var body = {};
                var contentType = response.headers.get('content-type') || '';

                if (contentType.indexOf('application/json') !== -1) {
                    try {
                        body = JSON.parse(text);
                    } catch (err) {
                        throw new Error('The API returned malformed JSON.');
                    }
                } else if (text) {
                    body = { message: htmlToText(text) };
                }

                if (!response.ok) {
                    throw new Error(body.message || body.error || 'API request failed with HTTP ' + response.status + '.');
                }

                if (contentType.indexOf('application/json') === -1) {
                    throw new Error('The API returned a non-JSON response.');
                }

                return body;
            });
        });
    }

    function setLoading(active) {
        loading.classList.toggle('d-none', !active);
    }

    function setError(message) {
        error.textContent = message || '';
        error.classList.toggle('d-none', !message);
    }

    function rowsToLabels(rows, key) {
        return rows.map(function (row) { return row[key || 'label'] || 'Unknown'; });
    }

    function rowsToValues(rows) {
        return rows.map(function (row) { return Number(row.value || 0); });
    }

    function datasetsHaveValues(datasets) {
        return datasets.some(function (dataset) {
            return (dataset.data || []).some(function (value) {
                return Number(value || 0) > 0;
            });
        });
    }

    function renderChart(id, type, labels, datasets, options) {
        var canvas = document.getElementById(id);
        if (!canvas) {
            return;
        }
        if (charts[id]) {
            charts[id].destroy();
        }

        var hasValues = labels.length > 0 && datasetsHaveValues(datasets);
        charts[id] = new Chart(canvas, {
            type: type,
            data: { labels: labels, datasets: datasets },
            options: Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 700 },
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    tooltip: { enabled: hasValues },
                    noDataMessage: { display: !hasValues }
                },
                scales: type === 'pie' || type === 'doughnut' || type === 'radar' ? {} : {
                    x: { title: { display: true, text: 'Category' } },
                    y: { beginAtZero: true, title: { display: true, text: 'Alumni count' }, ticks: { precision: 0 } }
                }
            }, options || {})
        });
    }

    function updateMetrics(summary) {
        document.getElementById('metric-alumni').textContent = summary.alumni_count;
        document.getElementById('metric-programmes').textContent = summary.programme_count;
        document.getElementById('metric-sectors').textContent = summary.sector_count;
        document.getElementById('metric-skills').textContent = summary.skill_count;
    }

    function updateInsights(insights) {
        var list = document.getElementById('insight-list');
        list.innerHTML = '';
        if (!insights.length) {
            list.innerHTML = '<div class="text-muted">No gap signals found for the selected filters.</div>';
            return;
        }
        insights.forEach(function (item) {
            var div = document.createElement('div');
            div.className = 'insight ' + item.severity;
            div.innerHTML = '<strong>' + escapeHtml(item.label) + '</strong><span>' +
                escapeHtml(item.category) + ' | ' + escapeHtml(item.severity) + ' | ' +
                item.coverage_percent + '% alumni evidence</span>';
            list.appendChild(div);
        });
    }

    function updateTable(rows) {
        var body = document.getElementById('alumni-rows');
        body.innerHTML = '';
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-muted">No alumni match the selected filters.</td></tr>';
            return;
        }
        rows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + escapeHtml(row.first_name + ' ' + row.last_name) + '</td>' +
                '<td>' + escapeHtml(row.programme) + '</td>' +
                '<td>' + escapeHtml(row.graduation_date) + '</td>' +
                '<td>' + escapeHtml(row.industry_sector) + '</td>' +
                '<td>' + escapeHtml(row.current_role || '') + '</td>' +
                '<td>' + escapeHtml(row.current_company || '') + '</td>';
            body.appendChild(tr);
        });
    }

    function matrixData(rows) {
        var labels = [];
        var sectors = [];
        rows.forEach(function (row) {
            if (labels.indexOf(row.programme) === -1) labels.push(row.programme);
            if (sectors.indexOf(row.sector) === -1) sectors.push(row.sector);
        });
        return {
            labels: labels,
            datasets: sectors.map(function (sector, index) {
                return {
                    label: sector,
                    data: labels.map(function (programme) {
                        var match = rows.find(function (row) { return row.programme === programme && row.sector === sector; });
                        return match ? Number(match.value) : 0;
                    }),
                    backgroundColor: palette[index % palette.length]
                };
            })
        };
    }

    function render(payload) {
        var data = payload.analytics;
        currentAnalytics = data;
        updateMetrics(data.summary);
        updateInsights(data.insights);
        updateTable(data.alumni);

        renderChart('programmeChart', 'bar', rowsToLabels(data.charts.programme_distribution), [{
            label: 'Alumni',
            data: rowsToValues(data.charts.programme_distribution),
            backgroundColor: palette[0]
        }]);

        renderChart('industryChart', 'doughnut', rowsToLabels(data.charts.industry_distribution), [{
            label: 'Alumni',
            data: rowsToValues(data.charts.industry_distribution),
            backgroundColor: palette
        }]);

        renderChart('graduationChart', 'line', rowsToLabels(data.charts.graduation_trend), [{
            label: 'Graduates',
            data: rowsToValues(data.charts.graduation_trend),
            borderColor: palette[1],
            backgroundColor: 'rgba(22, 163, 74, 0.15)',
            fill: true,
            tension: 0.35
        }]);

        renderChart('skillChart', 'bar', rowsToLabels(data.charts.skill_demand), [{
            label: 'Alumni Evidence',
            data: rowsToValues(data.charts.skill_demand),
            backgroundColor: data.charts.skill_demand.map(function (row) { return gapColors[row.curriculum_status] || palette[2]; })
        }], { indexAxis: 'y', scales: { x: { beginAtZero: true, title: { display: true, text: 'Alumni count' }, ticks: { precision: 0 } }, y: { title: { display: true, text: 'Skill' } } } });

        renderChart('gapChart', 'pie', rowsToLabels(data.charts.curriculum_gap), [{
            label: 'Evidence',
            data: rowsToValues(data.charts.curriculum_gap),
            backgroundColor: data.charts.curriculum_gap.map(function (row) { return gapColors[row.label] || palette[3]; })
        }]);

        renderChart('developmentChart', 'radar', rowsToLabels(data.charts.professional_development), [{
            label: 'Evidence Sources',
            data: rowsToValues(data.charts.professional_development),
            borderColor: palette[4],
            backgroundColor: 'rgba(124, 58, 237, 0.2)'
        }]);

        var matrix = matrixData(data.charts.sector_programme_matrix);
        renderChart('matrixChart', 'bar', matrix.labels, matrix.datasets, {
            scales: {
                x: { stacked: true, title: { display: true, text: 'Programme' } },
                y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Alumni count' }, ticks: { precision: 0 } }
            }
        });

        renderChart('biddingChart', 'doughnut', rowsToLabels(data.charts.bidding_outcomes), [{
            label: 'Bids',
            data: rowsToValues(data.charts.bidding_outcomes),
            backgroundColor: ['#16a34a', '#dc2626', '#64748b']
        }]);
    }

    function load() {
        setLoading(true);
        setError('');
        fetchJson('/analytics/overview', params())
            .then(render)
            .catch(function (err) { setError(err.message); })
            .finally(function () { setLoading(false); });
    }

    function exportUrl(base) {
        var query = params();
        return base + (query.toString() ? '?' + query.toString() : '');
    }

    function selectedText(fieldName) {
        var field = form.elements[fieldName];
        if (!field) return 'All';
        if (field.tagName === 'SELECT') {
            return field.selectedIndex > -1 ? field.options[field.selectedIndex].text : 'All';
        }
        return field.value || 'All';
    }

    function checked(name) {
        var field = form.querySelector('input[name="' + name + '"][type="checkbox"]');
        return field ? field.checked : true;
    }

    function addWrappedText(doc, text, x, y, width, lineHeight) {
        var lines = doc.splitTextToSize(String(text || ''), width);
        doc.text(lines, x, y);
        return y + (lines.length * lineHeight);
    }

    function drawMetricCard(doc, label, value, x, y, width, height) {
        doc.setFillColor(255, 255, 255);
        doc.setDrawColor(214, 220, 229);
        doc.roundedRect(x, y, width, height, 2.5, 2.5, 'FD');
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(92, 103, 118);
        doc.text(label.toUpperCase(), x + 5, y + 8);
        doc.setFont('times', 'bold');
        doc.setFontSize(18);
        doc.setTextColor(15, 23, 42);
        doc.text(String(value), x + 5, y + 21);
    }

    function addReportFooter(doc, page, totalPages) {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(100, 116, 139);
        doc.text('Alumni Influencers - University Analytics', 14, 201);
        doc.text('Page ' + page + ' of ' + totalPages, 267, 201);
    }

    function addSectionTitle(doc, title, x, y) {
        doc.setFont('times', 'bold');
        doc.setFontSize(15);
        doc.setTextColor(15, 23, 42);
        doc.text(title, x, y);
        doc.setDrawColor(37, 99, 235);
        doc.setLineWidth(0.6);
        doc.line(x, y + 3.5, x + 28, y + 3.5);
    }

    function addChartPanel(doc, chartId, x, y, width, height) {
        var chart = charts[chartId];
        if (!chart) {
            return;
        }

        doc.setFillColor(255, 255, 255);
        doc.setDrawColor(214, 220, 229);
        doc.roundedRect(x, y, width, height, 2.5, 2.5, 'FD');
        doc.setFont('times', 'bold');
        doc.setFontSize(12);
        doc.setTextColor(15, 23, 42);
        doc.text(chartTitles[chartId] || chartId, x + 6, y + 9);

        var canvas = chart.canvas;
        var ratio = canvas.width && canvas.height ? canvas.width / canvas.height : 1.7;
        var maxW = width - 14;
        var maxH = height - 24;
        var imgW = maxW;
        var imgH = imgW / ratio;

        if (imgH > maxH) {
            imgH = maxH;
            imgW = imgH * ratio;
        }

        var imgX = x + ((width - imgW) / 2);
        var imgY = y + 16 + ((maxH - imgH) / 2);
        doc.addImage(chart.toBase64Image('image/png', 1), 'PNG', imgX, imgY, imgW, imgH);
    }

    function generatePdfReport() {
        if (!currentAnalytics || !window.jspdf || !window.jspdf.jsPDF) {
            setError('The chart PDF generator is not ready. Refresh the page with Ctrl+F5, wait for charts to load, then try PDF again.');
            return;
        }

        var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        var summary = currentAnalytics.summary;
        var now = new Date();
        var y = 18;

        doc.setFillColor(11, 18, 32);
        doc.rect(0, 0, 297, 36, 'F');
        doc.setFillColor(37, 99, 235);
        doc.rect(0, 34, 297, 2, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('times', 'bold');
        doc.setFontSize(24);
        doc.text('University Analytics Report', 14, 17);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.text('Graduate outcome intelligence for curriculum planning and strategic review', 14, 27);
        doc.text('Generated ' + now.toLocaleString(), 238, 27);

        y = 48;
        addSectionTitle(doc, 'Current Filters', 14, y);
        y += 7;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(71, 85, 105);
        y = addWrappedText(doc, 'Programme: ' + selectedText('programme_id') + '     Sector: ' + selectedText('industry_sector_id') + '     Skill: ' + selectedText('skill_id'), 14, y, 265, 5);
        y = addWrappedText(doc, 'Graduation: ' + selectedText('graduation_from') + ' to ' + selectedText('graduation_to') + '     Keyword: ' + selectedText('keyword'), 14, y + 1, 265, 5);

        if (checked('metric_summary')) {
            y += 7;
            drawMetricCard(doc, 'Filtered Alumni', summary.alumni_count, 14, y, 61, 27);
            drawMetricCard(doc, 'Programmes', summary.programme_count, 82, y, 61, 27);
            drawMetricCard(doc, 'Industry Sectors', summary.sector_count, 150, y, 61, 27);
            drawMetricCard(doc, 'Observed Skills', summary.skill_count, 218, y, 61, 27);
            y += 40;
        }

        if (checked('metric_insights')) {
            addSectionTitle(doc, 'Priority Gap Signals', 14, y);
            y += 8;

            if (!currentAnalytics.insights.length) {
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);
                doc.setTextColor(100, 116, 139);
                y = addWrappedText(doc, 'No curriculum gap insights found for the selected filters.', 14, y, 265, 5);
            } else {
                currentAnalytics.insights.slice(0, 6).forEach(function (item, index) {
                    var col = index % 3;
                    var row = Math.floor(index / 3);
                    var x = 14 + (col * 91);
                    var yy = y + (row * 23);
                    doc.setFillColor(item.severity === 'critical' ? 254 : 248, item.severity === 'critical' ? 226 : 250, item.severity === 'critical' ? 226 : 252);
                    doc.setDrawColor(214, 220, 229);
                    doc.roundedRect(x, yy, 82, 17, 2, 2, 'FD');
                    doc.setFont('times', 'bold');
                    doc.setFontSize(9.5);
                    doc.setTextColor(15, 23, 42);
                    doc.text(item.label, x + 4, yy + 6);
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(7.5);
                    doc.setTextColor(71, 85, 105);
                    doc.text(item.severity.toUpperCase() + ' - ' + item.coverage_percent + '% evidence', x + 4, yy + 12);
                });
            }
        }

        Object.keys(chartTitles).forEach(function (chartId, index) {
            if (index % 2 === 0) {
                doc.addPage('a4', 'landscape');
                doc.setFillColor(248, 250, 252);
                doc.rect(0, 0, 297, 210, 'F');
                addSectionTitle(doc, 'Dashboard Visualisations', 14, 18);
            }

            var x = index % 2 === 0 ? 14 : 153;
            addChartPanel(doc, chartId, x, 34, 130, 142);
        });

        if (checked('metric_alumni')) {
            doc.addPage('a4', 'landscape');
            doc.setFillColor(248, 250, 252);
            doc.rect(0, 0, 297, 210, 'F');
            y = 18;
            addSectionTitle(doc, 'Alumni Sample', 14, y);
            y += 8;

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(8);
            doc.setTextColor(15, 23, 42);
            doc.setFillColor(226, 232, 240);
            doc.roundedRect(14, y - 5, 269, 9, 1.5, 1.5, 'F');
            doc.text('Name', 16, y);
            doc.text('Programme', 62, y);
            doc.text('Graduation', 147, y);
            doc.text('Industry', 178, y);
            doc.text('Current Role', 230, y);
            y += 8;

            currentAnalytics.alumni.slice(0, 18).forEach(function (row, index) {
                if (index % 2 === 0) {
                    doc.setFillColor(255, 255, 255);
                    doc.rect(14, y - 5, 269, 7, 'F');
                }
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7.5);
                doc.setTextColor(51, 65, 85);
                doc.text(String(row.first_name + ' ' + row.last_name).slice(0, 25), 16, y);
                doc.text(String(row.programme || '').slice(0, 48), 62, y);
                doc.text(String(row.graduation_date || '').slice(0, 12), 147, y);
                doc.text(String(row.industry_sector || '').slice(0, 28), 178, y);
                doc.text(String(row.current_role || '').slice(0, 30), 230, y);
                y += 7;
            });
        }

        var totalPages = doc.internal.getNumberOfPages();
        for (var page = 1; page <= totalPages; page++) {
            doc.setPage(page);
            addReportFooter(doc, page, totalPages);
        }

        doc.save('alumni-analytics-report-' + now.toISOString().slice(0, 10) + '.pdf');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
        });
    }

    function htmlToText(value) {
        var div = document.createElement('div');
        div.innerHTML = String(value || '');
        return (div.textContent || div.innerText || 'Server returned an HTML error response.').replace(/\s+/g, ' ').trim().slice(0, 500);
    }

    function setSidebarActive(hash) {
        var targetHash = hash || '#overview';
        document.querySelectorAll('.analytics-sidebar a').forEach(function (link) {
            link.classList.toggle('active', link.getAttribute('href') === targetHash);
        });
    }

    function initSidebarNavigation() {
        var links = Array.prototype.slice.call(document.querySelectorAll('.analytics-sidebar a'));
        if (!links.length) {
            return;
        }

        links.forEach(function (link) {
            link.addEventListener('click', function () {
                setSidebarActive(link.getAttribute('href'));
            });
        });

        window.addEventListener('hashchange', function () {
            setSidebarActive(window.location.hash);
        });

        if ('IntersectionObserver' in window) {
            var sections = links.map(function (link) {
                return document.querySelector(link.getAttribute('href'));
            }).filter(Boolean);

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        setSidebarActive('#' + entry.target.id);
                    }
                });
            }, { rootMargin: '-25% 0px -65% 0px', threshold: 0 });

            sections.forEach(function (section) {
                observer.observe(section);
            });
        }

        setSidebarActive(window.location.hash || '#overview');
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        load();
    });

    document.getElementById('reset-filters').addEventListener('click', function () {
        form.reset();
        load();
    });

    document.getElementById('download-csv').addEventListener('click', function () {
        window.location.href = exportUrl(root.getAttribute('data-csv-url'));
    });

    document.getElementById('download-pdf').addEventListener('click', function () {
        generatePdfReport();
    });

    document.getElementById('save-preset').addEventListener('click', function () {
        if (!savePresetModal) {
            setError('The preset dialog is not ready. Refresh the page and try again.');
            return;
        }
        presetNameInput.value = '';
        savePresetModal.show();
    });

    if (savePresetModalElement) {
        savePresetModalElement.addEventListener('shown.bs.modal', function () {
            presetNameInput.focus();
        });
    }

    savePresetForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var name = presetNameInput.value.trim();
        if (!name) return;
        var body = params();
        body.set('name', name);
        body.set(root.getAttribute('data-csrf-name'), root.getAttribute('data-csrf-hash'));
        fetch(root.getAttribute('data-save-preset-url'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
            body: body.toString()
        }).then(function (response) {
            return response.json().then(function (json) {
                if (json.csrf_hash) root.setAttribute('data-csrf-hash', json.csrf_hash);
                if (!response.ok) throw new Error(json.message || 'Could not save preset.');
                window.location.reload();
            });
        }).catch(function (err) { setError(err.message); });
    });

    document.getElementById('preset-select').addEventListener('change', function (event) {
        if (!event.target.value) return;
        try {
            var preset = JSON.parse(event.target.value);
            Object.keys(preset).forEach(function (key) {
                if (form.elements[key]) form.elements[key].value = preset[key] || '';
            });
            load();
        } catch (err) {
            setError('Saved preset could not be loaded.');
        }
    });

    document.querySelectorAll('.chart-download').forEach(function (button) {
        button.addEventListener('click', function () {
            var chart = charts[button.getAttribute('data-chart')];
            if (!chart) return;
            var a = document.createElement('a');
            a.href = chart.toBase64Image('image/png', 1);
            a.download = button.getAttribute('data-chart') + '.png';
            a.click();
        });
    });

    initSidebarNavigation();
    load();
}());
