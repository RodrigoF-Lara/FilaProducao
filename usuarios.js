document.addEventListener('DOMContentLoaded', () => {
    const userForm = document.getElementById('user-form');
    const listaUsuarios = document.getElementById('lista-usuarios');
    const formTitle = document.getElementById('form-title');
    const userIdInput = document.getElementById('user-id');
    const salvarBtn = document.getElementById('salvar-btn');
    const cancelarBtn = document.getElementById('cancelar-btn');

    const API_URL = 'usuarios_api.php';

    // =========================================================================
    // CARREGAR USUÁRIOS
    // =========================================================================
    async function carregarUsuarios() {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) {
                const errorResult = await response.json();
                throw new Error(errorResult.message || 'Erro ao buscar usuários');
            }
            const result = await response.json();
            if (result.success && Array.isArray(result.data)) {
                renderizarUsuarios(result.data);
            } else {
                alert('Não foi possível carregar os usuários: ' + (result.message || 'Formato de dados inválido.'));
            }
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    }

    // =========================================================================
    // RENDERIZAR TABELA
    // =========================================================================
    function renderizarUsuarios(usuarios) {
        listaUsuarios.innerHTML = '';
        usuarios.forEach(user => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${user.id}</td>
                <td>${user.usuario}</td>
                <td>${user.codigo_focco}</td>
                <td>${user.nome_completo}</td>
                <td>${user.email}</td>
                <td>${user.nivel}</td>
                <td>
                    <label class="switch">
                        <input type="checkbox" ${user.ativo ? 'checked' : ''} data-id="${user.id}">
                        <span class="slider round"></span>
                    </label>
                </td>
                <td>
                    <button class="edit-btn" data-id="${user.id}">Editar</button>
                    <button class="delete-btn" data-id="${user.id}">Remover</button>
                </td>
            `;
            listaUsuarios.appendChild(tr);
        });
    }
    
    // =========================================================================
    // LIMPAR FORMULÁRIO
    // =========================================================================
    function resetForm() {
        userForm.reset();
        userIdInput.value = '';
        formTitle.textContent = 'Novo Usuário';
        salvarBtn.textContent = 'Salvar';
        document.getElementById('senha').placeholder = '';
    }

    // =========================================================================
    // PREENCHER FORMULÁRIO PARA EDIÇÃO
    // =========================================================================
    function preencherFormulario(user) {
        formTitle.textContent = 'Editar Usuário';
        userIdInput.value = user.id;
        document.getElementById('usuario').value = user.usuario;
        document.getElementById('codigo_focco').value = user.codigo_focco;
        document.getElementById('nome_completo').value = user.nome_completo;
        document.getElementById('email').value = user.email;
        document.getElementById('ativo').value = String(user.ativo);
        document.getElementById('nivel').value = user.nivel || 'operador';
        document.getElementById('senha').value = ''; // Senha não é preenchida por segurança
        document.getElementById('senha').placeholder = 'Deixe em branco para não alterar';
    }


    // =========================================================================
    // SUBMISSÃO DO FORMULÁRIO (CRIAR/ATUALIZAR)
    // =========================================================================
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = userIdInput.value;
        const action = id ? 'update' : 'create';
        
        const formData = new FormData(userForm);
        const data = Object.fromEntries(formData.entries());
        data.action = action;
        data.ativo = data.ativo === 'true';

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                resetForm();
                carregarUsuarios();
            } else {
                throw new Error(result.message || 'Erro ao salvar usuário.');
            }
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    });

    // =========================================================================
    // BOTÃO CANCELAR
    // =========================================================================
    cancelarBtn.addEventListener('click', () => {
        resetForm();
    });

    // =========================================================================
    // AÇÕES NA LISTA (EDITAR, REMOVER, ATIVAR/DESATIVAR)
    // =========================================================================
    listaUsuarios.addEventListener('click', async (e) => {
        const target = e.target;
        const id = target.dataset.id;
        
        // --- Editar ---
        if (target.classList.contains('edit-btn')) {
            const response = await fetch(API_URL + `?id=${id}`);
            const result = await response.json();
            const userToEdit = result.data.find(u => u.id == id);
            if (userToEdit) {
                preencherFormulario(userToEdit);
                window.scrollTo(0, 0);
            }
        }

        // --- Remover ---
        if (target.classList.contains('delete-btn')) {
            if (confirm(`Tem certeza que deseja remover o usuário com ID ${id}?`)) {
                try {
                    const response = await fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id }),
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert(result.message);
                        carregarUsuarios();
                    } else {
                        throw new Error(result.message || 'Erro ao remover.');
                    }
                } catch (error) {
                    alert('Erro: ' + error.message);
                }
            }
        }
        
        // --- Ativar/Desativar ---
        if (target.matches('input[type="checkbox"]')) {
            const ativo = target.checked;
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', id: id, ativo: ativo }),
                });
                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Erro ao alterar status.');
                }
                 // Não precisa de alerta, a mudança visual é a confirmação
            } catch (error) {
                alert('Erro: ' + error.message);
                target.checked = !ativo; // Reverte a mudança visual em caso de erro
            }
        }
    });

    // =========================================================================
    // INICIALIZAÇÃO
    // =========================================================================
    carregarUsuarios();
});
