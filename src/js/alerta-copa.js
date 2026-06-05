
function abrirAlertaCopa(url) {
    Swal.fire({
        title: '⚽ Copa do Mundo 2026',
        text: 'Você será redirecionado para a tabela da Copa do Mundo 2026.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Ir para a tabela!',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

window.abrirAlertaCopa = abrirAlertaCopa;