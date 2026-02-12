// Adicionando um log para confirmar que a versão correta do arquivo foi carregada.
console.log('Versão corrigida do login.js carregada com sucesso.');

document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');
    const usuarioInput = document.getElementById('usuario');
    const senhaInput = document.getElementById('senha');

    // Limpa a sessão anterior para garantir um login limpo
    sessionStorage.clear();
    usuarioInput.focus();

    loginForm.addEventListener('submit', async function (event) {
        event.preventDefault(); // Impede o envio padrão do formulário
        errorMessage.style.display = 'none';
        errorMessage.textContent = '';

        const credentials = {
            usuario: usuarioInput.value.trim(),
            senha: senhaInput.value.trim()
        };

        try {
            const response = await fetch('login_auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(credentials)
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Falha na autenticação.');
            }

            // Armazena dados do usuário na sessão do navegador
            sessionStorage.setItem('usuario', result.data.usuario);
            sessionStorage.setItem('nome_completo', result.data.nome_completo);
            sessionStorage.setItem('codigo_focco', result.data.codigo_focco);
            sessionStorage.setItem('nivel', result.data.nivel);
            sessionStorage.setItem('logado', 'true'); // Flag para verificação de sessão

            // Redireciona para a página principal
            window.location.href = 'index.html';

        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.style.display = 'block';
        }
    });
});