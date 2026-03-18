@extends('layouts.app')

@section('title','Importar Nota')

@section('content')

<h1>Importar Notas (Multi Upload)</h1>

<div id="drop-area" style="
    border: 2px dashed #ccc;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    border-radius: 10px;
    margin-bottom: 20px;
">
    <p>Arraste PDFs aqui ou clique</p>
    <input type="file" id="fileInput" multiple hidden>
</div>

{{-- LISTA --}}
<table class="table">
    <thead>
        <tr>
            <th>Arquivo</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody id="fileList"></tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');

    // =========================
    // DRAG
    // =========================
    dropArea.addEventListener('click', () => fileInput.click());

    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        handleFiles(e.dataTransfer.files);
    });

    dropArea.addEventListener('dragover', e => e.preventDefault());

    fileInput.addEventListener('change', () => {
        handleFiles(fileInput.files);
    });

    // =========================
    // PROCESSAR ARQUIVOS
    // =========================
    function handleFiles(files) {

        Array.from(files).forEach(file => {

            if (file.type !== 'application/pdf') {
                Swal.fire('Erro', 'Somente PDF', 'error');
                return;
            }

            let row = document.createElement('tr');

            row.innerHTML = `
            <td>${file.name}</td>
            <td id="status-${file.name}">⏳ Enviando...</td>
        `;

            fileList.appendChild(row);

            uploadFile(file);
        });
    }

    // =========================
    // UPLOAD INDIVIDUAL
    // =========================
    function uploadFile(file) {

        let formData = new FormData();
        formData.append('file', file);

        let xhr = new XMLHttpRequest();
        xhr.open('POST', '/upload', true);
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

        xhr.onload = function() {

            let statusCell = document.getElementById('status-' + file.name);

            if (xhr.status === 200) {

                // 🔥 STATUS CORRETO (AGORA É FILA)
                statusCell.innerHTML = '⏳ Em processamento';

                // 🔥 SWEET ALERT
                if (!window.uploadAlertShown) {

                    window.uploadAlertShown = true;

                    Swal.fire({
                        icon: 'success',
                        title: 'Upload realizado!',
                        text: 'Arquivo enviado para processamento em segundo plano.',
                        confirmButtonColor: '#28a745'
                    });
                }

            } else {

                statusCell.innerHTML = '❌ Erro';

                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Falha ao enviar o arquivo',
                });
            }
        };

        xhr.send(formData);
    }
</script>

@endsection