# module-zbx-get-host-info

Consulta **read-only** da configuração completa de um host no Zabbix **7.0 LTS**:
dados gerais, interfaces, grupos, templates, tags, macros, inventário, itens e
triggers — tudo numa única tela, para perfis que normalmente não enxergam a
configuração pela UI nativa.

Porte para o padrão MVC do Zabbix 7.0 do módulo
[`get-host-ro`](https://framagit.org/zabbix-modules/get-host-ro) de
Mickael Martin (Cyres).

## O que mudou em relação ao original

- **Arquitetura MVC 7.0**: toda a coleta de API vive no controller
  (`actions/GetHostInfoView.php`); a view apenas renderiza.
- **Sem `page_header.php`/`page_footer.php` legados** — a página é montada pelo
  `layout.htmlpage` nativo.
- **Sem arquivos `.js`/`.css` estáticos** (DataTables removido). Filtro e
  ordenação de tabelas são feitos por JavaScript vanilla **inline**, compatível
  com ambientes atrás de F5 BIG-IP que bloqueiam estáticos.
- **Compatibilidade de API 7.0**: `proxy_hostid` → `proxyid`, proxy lido via
  `proxy.name`, severidades (nome + cor) vindas de `settings.get`.
- **Segurança**: todo dado externo passa por `htmlspecialchars` (corrige os XSS
  refletido/armazenado do original). Macros do tipo *Secret/Vault* aparecem como
  `*** secret ***`.
- **Correção de bug**: status/state das triggers agora usam o próprio trigger
  (antes herdavam o último item iterado).
- **UI**: layout em cards adaptável a tema claro/escuro, badges de status,
  cores de severidade configuradas, seções recolhíveis e busca por tabela.

> A API roda no contexto do usuário logado, portanto o filtro por grupo de
> permissão é respeitado: o usuário só consulta hosts dos grupos que pode ler.
> Ainda assim, o módulo **expõe detalhe de configuração** (keys, expressões,
> macros, inventário) que a UI esconde do perfil read-only — avalie se isso
> atende à sua política de segmentação antes de habilitar.

## Instalação

```bash
cd /usr/share/zabbix/modules
git clone https://github.com/leaoereno/module-zbx-get-host-info.git
chown -R apache:apache module-zbx-get-host-info   # AlmaLinux/Apache (Ubuntu: www-data)
chmod -R 644 module-zbx-get-host-info/*.php module-zbx-get-host-info/**/*.php
```

Depois: **Administration → General → Modules → Scan directory → Enable**.
O item aparece em **Monitoring → Host info (read-only)**.

Se as mudanças não refletirem: `systemctl restart php-fpm` (limpa OPcache).

## Estrutura

```
module-zbx-get-host-info/
├── manifest.json
├── Module.php
├── actions/
│   └── GetHostInfoView.php
└── views/
    └── gethostinfo.view.php
```

## Autoria

Porte: Rafael M. A. Leão Ereno — MALE · https://www.linkedin.com/in/leaoereno/
Módulo original: Mickael Martin (Cyres).
