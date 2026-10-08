<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Імпорт заявок</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f6fa; color: #333; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 24px; margin-bottom: 24px; color: #1a1a2e; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); padding: 28px; margin-bottom: 28px; }
        .card h2 { font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #444; }
        .upload-area { border: 2px dashed #cbd5e1; border-radius: 8px; padding: 40px 20px; text-align: center; cursor: pointer; transition: border-color .2s; }
        .upload-area:hover, .upload-area.drag { border-color: #3b82f6; background: #eff6ff; }
        .upload-area p { color: #64748b; margin-top: 8px; font-size: 14px; }
        .btn { display: inline-block; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; border: none; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-primary:disabled { background: #93c5fd; cursor: not-allowed; }
        .file-input { display: none; }
        .file-name { margin-top: 10px; font-size: 13px; color: #475569; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f8fafc; padding: 10px 12px; text-align: left; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        .badge-processing { background: #dbeafe; color: #1e40af; }
        .badge-done { background: #dcfce7; color: #166534; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        .link { color: #3b82f6; text-decoration: none; }
        .link:hover { text-decoration: underline; }
        .form-row { display: flex; align-items: center; gap: 12px; margin-top: 16px; }
        .pagination { display: flex; gap: 6px; margin-top: 16px; justify-content: center; }
        .pagination a, .pagination span { padding: 6px 10px; border-radius: 6px; font-size: 13px; text-decoration: none; }
        .pagination a { background: #e2e8f0; color: #334155; }
        .pagination a:hover { background: #cbd5e1; }
        .pagination .active { background: #3b82f6; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <h1>Імпорт заявок</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $e)
                {{ $e }}<br>
            @endforeach
        </div>
    @endif

    <div class="card">
        <h2>Завантажити файл (.xlsx)</h2>
        <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data" id="upload-form">
            @csrf
            <div class="upload-area" id="drop-zone" onclick="document.getElementById('file-input').click()">
                <svg width="40" height="40" fill="none" stroke="#94a3b8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 16V4m0 0L8 8m4-4 4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                </svg>
                <p>Перетягніть файл сюди або натисніть для вибору</p>
                <p>Підтримуються файли .xlsx, .xls (макс. 100 МБ)</p>
                <div class="file-name" id="file-name"></div>
            </div>
            <input type="file" name="file" id="file-input" class="file-input" accept=".xlsx,.xls">
            <div class="form-row">
                <button type="submit" class="btn btn-primary" id="submit-btn" disabled>Розпочати імпорт</button>
            </div>
        </form>
    </div>

    @if($imports->count())
    <div class="card">
        <h2>Історія імпортів</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Файл</th>
                    <th>Рядків</th>
                    <th>Оброблено</th>
                    <th>Помилок</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($imports as $import)
                <tr>
                    <td>{{ $import->id }}</td>
                    <td>{{ $import->original_name }}</td>
                    <td>{{ number_format($import->total_rows, 0, ',', ' ') }}</td>
                    <td>{{ number_format($import->processed_rows, 0, ',', ' ') }}</td>
                    <td>{{ $import->failed_rows }}</td>
                    <td><span class="badge badge-{{ $import->status }}">{{ $import->status }}</span></td>
                    <td>{{ $import->created_at->format('d.m.Y H:i') }}</td>
                    <td><a href="{{ route('import.status', $import->id) }}" class="link">Деталі</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $imports->links() }}</div>
    </div>
    @endif
</div>

<script>
    const input = document.getElementById('file-input');
    const submitBtn = document.getElementById('submit-btn');
    const fileNameEl = document.getElementById('file-name');
    const dropZone = document.getElementById('drop-zone');

    input.addEventListener('change', () => {
        if (input.files.length) {
            fileNameEl.textContent = 'Обрано: ' + input.files[0].name;
            submitBtn.disabled = false;
        }
    });

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag');
        const dt = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        input.files = dt.files;
        input.dispatchEvent(new Event('change'));
    });

    document.getElementById('upload-form').addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Завантаження...';
    });
</script>
</body>
</html>
