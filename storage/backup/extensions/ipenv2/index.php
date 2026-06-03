<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIGEP | Acesso Restrito</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com">

  <!-- Font Awesome Icons (URL Completa) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com">

  <!-- Theme style AdminLTE 3.2 (URL Completa) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

  <style>
    /* Estilo para centralizar e manter o visual dark do seu sistema */
    body.dark-mode {
      background-color: #454d55;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
      color: #fff;
    }
    .error-page {
      margin: 0 auto;
      text-align: center;
      width: 100%;
      max-width: 600px;
    }
    .error-page > .headline {
      font-size: 100px;
      font-weight: 300;
      display: block;
    }
    .error-content {
      margin-left: 0 !important;
    }
    .btn-outline-light:hover {
      color: #454d55;
    }
  </style>
</head>
<body class="hold-transition dark-mode">

<div class="error-page">
    <!-- Ícone de alerta do FontAwesome -->
    <h2 class="headline text-warning"><i class="fas fa-exclamation-triangle"></i></h2>

    <div class="error-content">
        <h3><br>Acesso Restrito.</h3>
        <p>
            Você está tentando acessar um recurso que não possui permissão.<br>
            Este incidente será reportado ao administrador do sistema <b>SIGEP</b>.
        </p>
        <div class="mt-4">
            <a href="/" class="btn btn-outline-light">Voltar para o Painel</a>
        </div>
    </div>
</div>

<!-- Scripts Necessários (URLs Completas) -->
<script src="https://cdnjs.cloudflare.com"></script>
<script src="https://cdnjs.cloudflare.com"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

</body>
</html>
