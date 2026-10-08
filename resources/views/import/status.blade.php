<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статус імпорту #{{ $import->id }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f6fa; color: #333; }
        .container { max-width: 700px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 22px; margin-bottom: 24px; color: #1a1a2e; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); padding: 28px; margin-bottom: 20px; }
        .meta { font-size: 14px; color: #64748b; margin-bottom: 20px; }
        .meta strong { color: #334155; }
        .progress-bar { background: #e2e8f0; border-radius: 20px; height: 14px; overflow: hidden; margin: 12px 0; }
        .progress-fill { height: 100%; background: #3b82f6; border-radius: 20px; transition: width .5s ease; }
        .progress-fill.done { background: #22c55e; }
        .progress-fill.failed { background: #ef4444; }
        .percent { font-size: 28px; font-weight: 700; color: #1e40af; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 20px 0; }
        .stat { background: #f8fafc; border-radius: 8px; padding: 14px; text-align: center; }
        .stat-value { font-size: 22px; font-weight: 700; color: #0f172a; }
        .stat-label { font-size: 12px; color: #64748b; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-processing { background: #dbeafe; color: #1e40af; }
        .badge-done { background: #dcfce7; color: #166534; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; padding: 10px 22px; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; margin-right: 10px; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }
        .error-box { background: #fee2e2; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #7f1d1d; margin-top: 16px; }
        .spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid #93c5fd; border-top-color: #3b82f6; border-radius: 50%; animation: spin .8s linear infinite; vertical-align: middle; margin-right: 6px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="container">
    <h1>Імпорт #{{ $import->id }}</h1>

    <div class="card">
        <div class="meta">
            <strong>Файл:</strong> {{ $import->original_name }}<br>
            <strong>Розпочато:</strong> {{ $import->created_at->format('d.m.Y H:i:s') }}<br>
            <strong>Статус:</strong> <span class="badge badge-{{ $import->status }}" id="status-badge">{{ $import->status }}</span>
        </div>

        <div id="progress-section">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                <span style="font-size:13px; color:#64748b;" id="progress-label">
                    @if($import->status === 'processing')
                        <span class="spinner"></span>Обробка...
                    @elseif($import->status === 'done')
                        Завершено
                    @elseif($import->status === 'failed')
                        Помилка
                    @else
                        Очікування...
                    @endif
                </span>
                <span class="percent" id="percent">{{ $import->getProgressPercent() }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill {{ $import->isDone() ? 'done' : ($import->isFailed() ? 'failed' : '') }}"
                     id="progress-fill"
                     style="width: {{ $import->getProgressPercent() }}%"></div>
            </div>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-value" id="stat-total">{{ number_format($import->total_rows, 0, ',', ' ') }}</div>
                <div class="stat-label">Всього рядків</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="stat-processed">{{ number_format($import->processed_rows, 0, ',', ' ') }}</div>
                <div class="stat-label">Оброблено</div>
            </div>
            <div class="stat">
                <div class="stat-value" id="stat-failed">{{ $import->failed_rows }}</div>
                <div class="stat-label">Помилок</div>
            </div>
        </div>

        @if($import->error_message)
            <div class="error-box" id="error-box">{{ $import->error_message }}</div>
        @else
            <div class="error-box" id="error-box" style="display:none;"></div>
        @endif
    </div>

    <a href="{{ route('import.index') }}" class="btn btn-secondary">← Назад до імпортів</a>
</div>

@if(!$import->isDone() && !$import->isFailed())
<script>
    const jsonUrl = '{{ route('import.status.json', $import->id) }}';
    let interval;

    function fmt(n) {
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function poll() {
        fetch(jsonUrl)
            .then(r => r.json())
            .then(data => {
                document.getElementById('percent').textContent = data.progress + '%';
                document.getElementById('stat-total').textContent = fmt(data.total_rows);
                document.getElementById('stat-processed').textContent = fmt(data.processed_rows);
                document.getElementById('stat-failed').textContent = data.failed_rows;

                const fill = document.getElementById('progress-fill');
                fill.style.width = data.progress + '%';

                const badge = document.getElementById('status-badge');
                badge.textContent = data.status;
                badge.className = 'badge badge-' + data.status;

                const label = document.getElementById('progress-label');

                if (data.status === 'done') {
                    fill.classList.add('done');
                    label.innerHTML = 'Завершено';
                    clearInterval(interval);
                } else if (data.status === 'failed') {
                    fill.classList.add('failed');
                    label.innerHTML = 'Помилка';
                    if (data.error_message) {
                        const eb = document.getElementById('error-box');
                        eb.textContent = data.error_message;
                        eb.style.display = '';
                    }
                    clearInterval(interval);
                } else if (data.status === 'processing') {
                    label.innerHTML = '<span class="spinner"></span>Обробка...';
                }
            });
    }

    interval = setInterval(poll, 2000);
    poll();
</script>
@endif
</body>
</html>
