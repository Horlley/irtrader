<!DOCTYPE html>
<html>

<head>
    <title>IR Trader</title>
</head>

<body>

    <h2>Importar Nota de Corretagem</h2>

    @if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('upload.pdf') }}" enctype="multipart/form-data">

        @csrf

        <input type="file" name="pdf">

        <button type="submit">Enviar</button>

    </form>

</body>

</html>