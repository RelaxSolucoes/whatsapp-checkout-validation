=== Validação de WhatsApp no Checkout ===
Contributors: relaxsolucoes
Tags: whatsapp, checkout, woocommerce, validation
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Valide em tempo real se o telefone do checkout possui WhatsApp e alerte o cliente caso não tenha.

== Description ==
Este plugin verifica, via API Evolution, se o número informado no checkout é WhatsApp. Caso não seja, exibe um aviso e pede confirmação do cliente.

== Features ==
- Validação em tempo real via AJAX
- Mensagem de aviso personalizável
- Prefixo internacional configurável (padrão 55)
- Cache de resultados para reduzir chamadas à API
- Totalmente traduzível e compatível com WooCommerce

== Installation ==
1. Envie a pasta do plugin para `wp-content/plugins` ou instale via painel.
2. Ative o plugin.
3. Acesse Configurações → Validação WhatsApp e informe URL, chave da API e instância.

== Changelog ==
= 1.1.0 =
* Melhoria: CSS dedicado e i18n no JS
* Melhoria: Prefixo internacional configurável
* Melhoria: Cache com transients e filtros de extensão (`wcv_*`)
* Melhoria: link de Configurações na lista de plugins

= 1.0.1 =
* Correções menores

= 1.0.0 =
* Versão inicial

