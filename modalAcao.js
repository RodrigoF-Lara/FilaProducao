// modalAcao.js
// Lógica do modal de ação separada para modularidade

// Elementos do modal
const modalContainer = document.getElementById('modal-container');
const modalTitle = document.getElementById('modal-title');
const modalBody = document.getElementById('modal-body');
const modalCloseBtn = document.getElementById('modal-close-btn');

// Função para abrir o modal genérico com conteúdo dinâmico
const abrirModal = (title, content) => {
    if (modalContainer && modalTitle && modalBody) {
        modalTitle.innerHTML = title;
        modalBody.innerHTML = content;
        modalContainer.style.display = 'flex';
    }
};

// Função para fechar o modal genérico
const fecharModal = () => {
    if (modalContainer) modalContainer.style.display = 'none';
};

if (modalCloseBtn) {
    modalCloseBtn.addEventListener('click', fecharModal);
}

window.onclick = function(event) {
    if (event.target == modalContainer) {
        fecharModal();
    }
};

// Outras funções relacionadas ao modal podem ser movidas para cá conforme necessário.
