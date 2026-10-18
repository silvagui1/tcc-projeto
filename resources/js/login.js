// Tela de login — botão de mostrar/esconder a senha.
const botao = document.querySelector('[data-ver-senha]');
const senha = document.getElementById('password');

if (botao && senha) {
    botao.addEventListener('click', () => {
        const mostrar = senha.type === 'password';
        senha.type = mostrar ? 'text' : 'password';
        botao.setAttribute('aria-pressed', String(mostrar));
        botao.setAttribute('aria-label', mostrar ? 'Esconder senha' : 'Mostrar senha');
        botao.querySelector('i').className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
        senha.focus();
    });
}

// evita o duplo clique enviar o formulário duas vezes
const form = document.querySelector('.login__form');
form?.addEventListener('submit', () => {
    form.querySelector('.login__entrar').disabled = true;
});
