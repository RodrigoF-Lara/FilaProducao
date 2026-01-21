====================================================================
     COMO COMPARTILHAR O ARQUIVO INDEX.HTML COM OUTRAS PESSOAS
====================================================================

PROBLEMA:
Quando você envia o arquivo index.html para outras pessoas, os dados
não aparecem devido a restrições de segurança dos navegadores.

SOLUÇÕES:

─────────────────────────────────────────────────────────────────────
1. USAR UM SERVIDOR LOCAL (RECOMENDADO PARA TESTES)
─────────────────────────────────────────────────────────────────────

OPÇÃO A - Com Python (mais fácil):
   1. Abra o Prompt de Comando ou PowerShell
   2. Navegue até a pasta do projeto:
      cd "C:\Users\ADM\Documents\Projetos\Fila Mercado"
   3. Execute:
      python -m http.server 8000
   4. Abra no navegador:
      http://localhost:8000

OPÇÃO B - Com Node.js:
   1. Instale o http-server (uma vez):
      npm install -g http-server
   2. Na pasta do projeto, execute:
      http-server -p 8000
   3. Abra no navegador:
      http://localhost:8000

─────────────────────────────────────────────────────────────────────
2. HOSPEDAR ONLINE (RECOMENDADO PARA COMPARTILHAR)
─────────────────────────────────────────────────────────────────────

Hospede o arquivo gratuitamente em:

• GitHub Pages (https://pages.github.com/)
  - Crie um repositório no GitHub
  - Faça upload do index.html
  - Ative GitHub Pages nas configurações
  - Compartilhe o link gerado

• Netlify (https://www.netlify.com/)
  - Arraste e solte a pasta no site
  - Receba um link instantaneamente

• Vercel (https://vercel.com/)
  - Similar ao Netlify
  - Deploy automático

─────────────────────────────────────────────────────────────────────
3. VERIFICAR PERMISSÕES DO GOOGLE SHEETS
─────────────────────────────────────────────────────────────────────

IMPORTANTE: A planilha precisa estar pública!

1. Abra a planilha no Google Sheets
2. Clique em "Compartilhar" (canto superior direito)
3. Em "Acesso geral", escolha:
   ☑ "Qualquer pessoa com o link"
   ☑ Permissão: "Visualizador"
4. Clique em "Concluído"

5. Vá em Arquivo → Compartilhar → Publicar na web
6. Escolha:
   ☑ Formato: CSV
   ☑ Marque: "Publicar automaticamente"
7. Clique em "Publicar"

─────────────────────────────────────────────────────────────────────

Qualquer dúvida, consulte a mensagem de erro no próprio site!
