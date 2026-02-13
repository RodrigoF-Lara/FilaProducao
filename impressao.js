// impressao.js
window.imprimirDemandasModal = function(numero_ordem, modalContentHtml, cod_produto = '', descricao = '', data = '', cliente = '', quantidade = '') {
    const printWindow = window.open('', '', 'width=1200,height=900');
    if (!printWindow) return alert('Não foi possível abrir a janela de impressão.');

    const style = `
        <style>
            @media print {
                .folha-ordem {
                    page-break-after: always;
                    width: 100vw;
                    height: 100vh;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    writing-mode: horizontal-tb;
                }
                .numero-ordem-grande {
                    font-size: 18vw;
                    font-weight: bold;
                    text-align: center;
                    width: 100vw;
                    color: #222;
                }
                .info-ordem {
                    font-size: 1.6vw;
                    margin-top: 1vw;
                    text-align: center;
                }
                .info-ordem span {
                    display: block;
                    margin: 0.2vw 0;
                }
                .folha-modal {
                    width: 100vw;
                    min-height: 100vh;
                }
                body {
                    margin: 0;
                    padding: 0;
                }
            }
            @page {
                size: A4 landscape;
                margin: 0;
            }
        </style>
    `;

    const html = `
        <html>
        <head>
            <title>Impressão da Ordem ${numero_ordem}</title>
            ${style}
        </head>
        <body>
            <div class="folha-ordem">
                <div class="numero-ordem-grande">${numero_ordem}</div>
                <div class="info-ordem">
                    ${cod_produto ? `<span><strong>Código:</strong> ${cod_produto}</span>` : ''}
                    ${descricao ? `<span><strong>Descrição:</strong> ${descricao}</span>` : ''}
                    ${quantidade ? `<span><strong>Quantidade:</strong> ${quantidade}</span>` : ''}
                    ${data ? `<span><strong>Data Pedido:</strong> ${data}</span>` : ''}
                    ${cliente ? `<span><strong>Cliente:</strong> ${cliente}</span>` : ''}
                </div>
            </div>
            <div class="folha-modal">
                ${modalContentHtml}
            </div>
            <script>
                window.onload = function() { window.print(); }
            <\/script>
        </body>
        </html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
};
