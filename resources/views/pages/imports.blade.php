@extends('layouts.app')

@section('title','Importar Nota')

@section('content')

<div x-data="{tab:'upload'}">

    <!-- 🔥 TABS -->
    <div style="display:flex; gap:10px; margin-bottom:20px;">
        
        <button @click="tab='upload'"
            :style="tab=='upload' ? activeTab : inactiveTab">
            ⬆️ Upload em Massa
        </button>

        <button @click="tab='list'"
            :style="tab=='list' ? activeTab : inactiveTab">
            📂 Arquivos Enviados
        </button>

    </div>

    <!-- ========================= -->
    <!-- 🔹 UPLOAD -->
    <!-- ========================= -->
    <div x-show="tab=='upload'">

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

    </div>

    <!-- ========================= -->
    <!-- 🔹 LISTA -->
    <!-- ========================= -->
    <div x-show="tab=='list'">

        <h2>Arquivos enviados</h2>

        <table class="table">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="fileList"></tbody>
        </table>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');

    // =========================
    // ESTILO TABS
    // =========================
    const activeTab = "background:#2563eb;color:#fff;padding:8px 14px;border-radius:6px;border:none;";
    const inactiveTab = "background:#e5e7eb;padding:8px 14px;border-radius:6px;border:none;";

    // =========================
    // DRAG
    // =========================
    if (dropArea) {

        dropArea.addEventListener('click', () => fileInput.click());

        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            handleFiles(e.dataTransfer.files);
        });

        dropArea.addEventListener('dragover', e => e.preventDefault());
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            handleFiles(fileInput.files);
        });
    }

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

                statusCell.innerHTML = '⏳ Em processamento';

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