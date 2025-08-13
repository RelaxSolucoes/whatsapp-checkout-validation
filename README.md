# Validação de WhatsApp no Checkout (Evolution API)

Verifica, em tempo real, se o telefone informado no checkout do WooCommerce possui WhatsApp usando sua própria instalação da Evolution API. Exibe mensagens de sucesso/erro, e (opcionalmente) um modal de confirmação quando o número não é WhatsApp.

## Recursos
- Validação em tempo real via AJAX no checkout
- Mensagens e modal personalizáveis (Configuração)
- Aba Conexão com “🧪 Testar Conexão”
- Cache com transients (com rotação automática ao salvar configurações)
- Compatível com WooCommerce HPOS e Cart/Checkout Blocks
- Auto‑update via GitHub (plugin-update-checker embarcado)
- Totalmente traduzível (i18n + `languages/`)

## Requisitos
- WordPress ≥ 5.6
- PHP ≥ 7.4
- WooCommerce ≥ 5.0
- Evolution API ativa (URL, API Key e Nome da Instância)

## Instalação
1. Copie a pasta para `wp-content/plugins/` ou instale via ZIP no painel.
2. Ative o plugin.

## Configuração
1. Se ainda não tem Evolution API, use o CTA do topo para criar sua conta de teste. Você receberá: URL da API, API Key e Nome da Instância.
2. Vá em: Admin → Validação WhatsApp → aba “Conexão”.
3. Preencha: 🌐 URL da API, 🔑 API Key, 📱 Nome da Instância.
4. Clique em “💾 Salvar Configurações” e depois em “🧪 Testar Conexão”.
5. Na aba “Configuração”, ajuste:
   - Ativar validação no checkout
   - Exibir modal de confirmação (opcional)
   - Mensagem de sucesso / erro
   - Título e texto do botão do modal

## Comportamento no checkout
- Enquanto o cliente digita, o plugin verifica o número via AJAX.
- Mostra mensagem verde (válido) ou laranja/vermelha (inválido/erro).
- Se o número não é WhatsApp e o modal estiver habilitado, é exibida uma confirmação antes de finalizar o pedido.

## Segurança e qualidade
- Nonce em requisições AJAX; sanitização/escape das entradas.
- Requests via `wp_remote_*` com timeout, cabeçalhos e erros tratados.
- Cache por número de telefone (com salt rotativo após mudanças de configuração).

## Hooks para desenvolvedores
- `wcv_validation_api_url( string $apiUrl, string $phone )`
- `wcv_validation_request_args( array $args, string $phone )`
- `wcv_validation_response( array $result, array $rawData )`
- `wcv_validation_cache_ttl( int $seconds, array $result )`

## Internacionalização (i18n)
- Text domain: `whatsapp-checkout-validation`
- Arquivos em `languages/` (`.pot` e `pt_BR.po`).

## Auto‑update via GitHub
- Lib: `lib/plugin-update-checker` (v5.x)
- Repositório: `RelaxSolucoes/whatsapp-checkout-validation`
- Branch padrão: `main`
- Publique tags semânticas (ex.: `v1.0.0`) para distribuir atualizações.

## Desenvolvimento
Arquivos principais:
- `whatsapp-checkout-validation.php` (bootstrap, hooks, auto‑update, HPOS)
- `includes/class-wcv-validator.php` (enqueue + AJAX)
- `includes/admin/class-wcv-admin-settings.php` (UI com abas, “Testar Conexão”)
- `assets/js/wcv-frontend.js` (validação em tempo real)
- `assets/css/` (`admin.css`, `wcv-frontend.css`)

## Licença
GPLv2 ou posterior. Consulte `readme.txt` e `uninstall.php`.

## Créditos
Desenvolvido por [Relax Soluções](https://relaxsolucoes.online). Repositório: `https://github.com/RelaxSolucoes/whatsapp-checkout-validation`.


