# module-zbx-get-host-info

**Get Host Info (read-only)** — consulta da configuração completa de um host no
Zabbix **7.0 LTS** numa única tela: dados gerais, interfaces, grupos, templates,
tags, macros, inventário, itens e triggers. Pensado para perfis que normalmente
não enxergam a configuração pela UI nativa.

Porte para o padrão MVC do Zabbix 7.0 do módulo
[`get-host-ro`](https://framagit.org/zabbix-modules/get-host-ro) de
Mickael Martin (Cyres).

| | |
|---|---|
| **ID** | `module-zbx-get-host-info` |
| **Versão** | `7.1.0` |
| **Compatibilidade** | Zabbix 7.0 LTS |
| **Namespace** | `GetHostInfo` |
| **Action** | `gethostinfo.view` |

## Recursos

- Busca de host por nome técnico **ou** display name, com autocomplete.
- Visão consolidada em **cards**: geral, macros, inventário, itens e triggers.
- Filtro e ordenação por coluna em cada tabela, sem recarregar a página.
- Badges de status, *pills* de enabled/disabled e normal/not-supported.
- Severidade das triggers com **nome e cor configurados** no Zabbix.
- Layout adaptável a **tema claro e escuro**.

## Notas de segurança

- A API roda no contexto do usuário logado, portanto o filtro por **grupo de
  permissão é respeitado**: o usuário só consulta hosts dos grupos que pode ler.
- O módulo expõe detalhe de configuração (keys, expressões, macros, inventário)
  que a UI esconde do perfil read-only — avalie se isso atende à sua política de
  segmentação antes de habilitar.
- Macros do tipo **Secret/Vault** aparecem como `*** secret ***` (a API não
  retorna o valor).
- Todo dado externo passa por `htmlspecialchars` (sem vetores de XSS).

## Requisitos

- Zabbix Frontend 7.0 LTS (PHP 8.x).
- Acesso de leitura aos grupos de host que se deseja consultar.

## Instalação

```bash
cd /usr/share/zabbix/modules
git clone https://github.com/leaoereno/module-zbx-get-host-info.git

# AlmaLinux / Apache (Ubuntu: www-data)
chown -R apache:apache module-zbx-get-host-info
find module-zbx-get-host-info -type f -name '*.php' -exec chmod 644 {} \;
```

Depois, na interface: **Administration → General → Modules → Scan directory**,
e habilite **Get Host Info (read-only)**.
O item aparece em **Monitoring → Host info (read-only)**.

> Se as mudanças não refletirem, limpe o OPcache: `systemctl restart php-fpm`.

## Estrutura

```
module-zbx-get-host-info/
├── manifest.json                 # metadados, roteamento (manifest_version 2)
├── Module.php                    # CModule — item de menu (try/catch)
├── actions/
│   └── GetHostInfoView.php       # CController — toda a coleta via API
└── views/
    └── gethostinfo.view.php      # render only (escape) + CSS/JS inline
```

Arquitetura MVC: o **controller** concentra toda a lógica de API e entrega um
array já normalizado; a **view** apenas renderiza. JS/CSS 100% inline — sem
arquivos `.js`/`.css` estáticos (compatível com F5 BIG-IP, que os bloqueia).

## Changelog

### 7.1.0
- UI/UX: migração completa da paleta customizada (`--ghi-*`) para os tokens
  canônicos do design system dos módulos (`--c-*`), reutilizados em `.badge`,
  `.card`, `.pill`, `.empty`, tabelas e barra de filtro.
- UI/UX: título de página com emoji (📋) e ícone `zi-search` no item de menu,
  seguindo a convenção `zi-*` no menu / emoji nas views.
- UI/UX: estados de host não encontrado / múltiplos resultados / erro agora
  usam o componente canônico `.empty` (ícone + título + descrição), com texto
  em PT-BR (convenção: rótulos estruturais em inglês, mensagens ao usuário em
  português).
- UI/UX: novo estado `error` — falhas de API (permissão revogada, host removido
  entre a busca e a coleta, etc.) mostram um card de erro em vez de deixar a
  página quebrar.
- Correção: `API::Settings()->get()` (cores/nomes de severidade) agora tem
  fallback para a paleta padrão do Zabbix quando o método `settings.get` foi
  revogado numa role customizada — antes, isso derrubava a tela inteira sem
  mensagem clara.
- Correção: coleta do host (`collectHostInfo`) agora trata o caso em que o
  host some entre a resolução do nome e a busca detalhada (ex.: removido por
  outro usuário durante a navegação), lançando um erro tratado em vez de
  cascatear avisos com `$host = null`.
- Correção: checagem redundante de `proxyid !== '0'` removida (`!empty()` já
  cobre o caso).
- Melhoria: `<datalist>` de autocomplete agora tem limite de segurança
  (3000 hosts) e é resiliente a falha da API — se o autocomplete falhar, a
  busca exata continua funcionando normalmente.
- Melhoria: removida variável JS não utilizada (`root`) no script inline.

### 7.0.0
- Porte completo para o padrão MVC do Zabbix 7.0 (lógica no controller).
- Remoção dos includes legados `page_header.php` / `page_footer.php`.
- Remoção do DataTables estático; filtro e ordenação em JavaScript vanilla inline.
- Compatibilidade de API 7.0: `proxy_hostid` → `proxyid`, proxy via `proxy.name`,
  severidades (nome + cor) via `settings.get`, inventário via `selectInventory: extend`.
- Correção: status/state das triggers passam a usar o próprio trigger.
- Segurança: escape de todo dado externo; macros Secret/Vault mascaradas.
- UI: layout em cards adaptável a tema claro/escuro, badges, *pills* de estado,
  seções recolhíveis e busca por tabela.
- Controles de pesquisa usando componentes nativos do Zabbix (`CTextBox`/`CSubmit`).
- Espaçamento da página ajustado em relação à sidebar nativa.

## Autoria

Porte e manutenção: **Rafael M. A. Leão Ereno — MALE**
LinkedIn: https://www.linkedin.com/in/leaoereno/ · GitHub: https://github.com/leaoereno

Módulo original: **Mickael Martin** (Cyres) —
https://framagit.org/zabbix-modules/get-host-ro
