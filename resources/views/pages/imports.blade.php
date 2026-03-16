@extends('layouts.app')

@section('title','Importar Nota')

@section('content')

<h1>Importar Nota de Corretagem</h1>

<form method="POST" action="/upload" enctype="multipart/form-data">

    @csrf

    <input type="file" name="pdf">

    <button type="submit">
        Enviar
    </button>

</form>

@endsection