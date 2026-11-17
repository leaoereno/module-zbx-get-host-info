<?php

namespace Modules\GetHostInfo;

/**
 * Pequeno dicionario EN -> PT-BR das strings proprias do modulo.
 *
 * O _() do core do Zabbix so traduz strings presentes nos catalogos .mo do
 * proprio Zabbix — strings definidas dentro de um modulo nunca sao traduzidas
 * por ele, mesmo que o usuario tenha o frontend em outro idioma. Por isso o
 * modulo mantem seu proprio catalogo aqui e decide o idioma a partir do que
 * o usuario escolheu em Administration -> Users (users.lang/CWebUser), em
 * vez de uma configuracao propria — assim ele sempre acompanha o idioma do
 * restante do Zabbix (EN continua EN, PT-BR passa a usar este dicionario).
 *
 * Mesmo padrao usado em module-zbx-flapping-detector/Lang.php.
 */
class Lang {

	private const STRINGS = [
		// Nome do modulo / item de menu — em PT o modulo passa a se chamar
		// "Ciclo de vida do Host" (rebranding pedido pelo usuario; em EN o
		// nome original "Host info (read-only)" permanece).
		'Host info (read-only)' => 'Ciclo de vida do Host',

		// Usado tambem para localizar/criar o submenu "SRE > Troubleshooting"
		// em Module.php — ver nota la sobre reaproveitar o submenu existente
		// em qualquer um dos dois idiomas antes de criar um novo.
		'Troubleshooting' => 'Solução de problemas',

		// --- Busca -------------------------------------------------------
		'Technical name or display name...' => 'Nome técnico ou nome de exibição...',
		'Host' => 'Host',
		'Search host' => 'Buscar host',

		// --- Estado: erro --------------------------------------------------
		'Could not load host data' => 'Não foi possível carregar os dados do host',
		'Unknown error. Try again shortly.' => 'Erro desconhecido. Tente novamente em instantes.',

		// --- Estado: multiplos resultados -----------------------------------
		'More than one host found' => 'Mais de um host encontrado',
		'The pattern "%1$s" returned %2$d hosts. Refine your search and try again.' =>
			'O padrão "%1$s" retornou %2$d hosts. Refine a busca e tente novamente.',

		// --- Estado: nenhum resultado ----------------------------------------
		'No host found' => 'Nenhum host encontrado',
		'The pattern "%s" returned no hosts. Check your search and try again.' =>
			'O padrão "%s" não retornou nenhum host. Verifique a busca e tente novamente.',

		// --- Estado: vazio (tela inicial) -----------------------------------
		'Search for a host' => 'Busque um host',
		'Type the host\'s technical or display name above and click "Search host".' =>
			'Digite o nome técnico ou de exibição do host acima e clique em "Buscar host".',

		// --- Badges do cabecalho do host -------------------------------------
		'Monitored' => 'Monitorado',
		'Not monitored' => 'Não monitorado',
		'In maintenance' => 'Em manutenção',
		'Proxy' => 'Proxy',
		'none' => 'nenhum',
		'items' => 'itens',
		'triggers' => 'gatilhos',
		'macros' => 'macros',

		// --- Titulos dos cards ------------------------------------------
		'General' => 'Geral',
		'Macros' => 'Macros',
		'Inventory' => 'Inventário',
		'Items' => 'Itens',
		'Triggers' => 'Gatilhos',

		// --- Card Geral ----------------------------------------------------
		'Host name' => 'Nome do host',
		'Display name' => 'Nome de exibição',
		'Status' => 'Status',
		'Maintenance' => 'Manutenção',
		'None' => 'Nenhuma',
		'No proxy' => 'Sem proxy',
		'Description' => 'Descrição',
		'Interfaces' => 'Interfaces',
		'No interfaces' => 'Sem interfaces',
		'Groups' => 'Grupos',
		'Templates' => 'Templates',
		'Tags' => 'Tags',

		// --- Card Macros ---------------------------------------------------
		'Filter macros...' => 'Filtrar macros...',
		'Macro' => 'Macro',
		'Value' => 'Valor',
		'secret' => 'secreta',
		'No macros' => 'Nenhuma macro',

		// --- Card Inventario -------------------------------------------------
		'Empty or disabled inventory.' => 'Inventário vazio ou desabilitado.',

		// --- Card Itens ----------------------------------------------------
		'Filter items...' => 'Filtrar itens...',
		'Name' => 'Nome',
		'Key' => 'Chave',
		'Delay' => 'Intervalo',
		'History' => 'Histórico',
		'Trends' => 'Tendências',
		'State' => 'Estado',
		'Enabled' => 'Habilitado',
		'Disabled' => 'Desabilitado',
		'Normal' => 'Normal',
		'Not supported' => 'Não suportado',
		'No items' => 'Nenhum item',

		// --- Card Triggers --------------------------------------------------
		'Filter triggers...' => 'Filtrar gatilhos...',
		'Severity' => 'Severidade',
		'Expression (expanded)' => 'Expressão (expandida)',
		'Unknown' => 'Desconhecido',
		'No triggers' => 'Nenhum gatilho',

		// --- Severidade padrao (fallback quando settings.get falha) -----------
		'Not classified' => 'Não classificado',
		'Information' => 'Informação',
		'Warning' => 'Aviso',
		'Average' => 'Média',
		'High' => 'Alta',
		'Disaster' => 'Desastre',

		// --- Campos de inventario (labels amigaveis) --------------------------
		'Type' => 'Tipo',
		'Type (full)' => 'Tipo (completo)',
		'Alias' => 'Apelido',
		'OS' => 'SO',
		'OS (full)' => 'SO (completo)',
		'OS (short)' => 'SO (resumido)',
		'Serial A' => 'Nº de série A',
		'Serial B' => 'Nº de série B',
		'Tag' => 'Tag',
		'Asset tag' => 'Nº do patrimônio',
		'MAC A' => 'Endereço MAC A',
		'MAC B' => 'Endereço MAC B',
		'Hardware' => 'Hardware',
		'Hardware (full)' => 'Hardware (completo)',
		'Software' => 'Software',
		'Software (full)' => 'Software (completo)',
		'Software app A' => 'Aplicativo de software A',
		'Software app B' => 'Aplicativo de software B',
		'Software app C' => 'Aplicativo de software C',
		'Software app D' => 'Aplicativo de software D',
		'Software app E' => 'Aplicativo de software E',
		'Contact' => 'Contato',
		'Location' => 'Localização',
		'Latitude' => 'Latitude',
		'Longitude' => 'Longitude',
		'Notes' => 'Notas',
		'Chassis' => 'Chassi',
		'Model' => 'Modelo',
		'HW architecture' => 'Arquitetura de HW',
		'Vendor' => 'Fornecedor',
		'Contract number' => 'Número do contrato',
		'Installer' => 'Instalador',
		'Deployment status' => 'Status de implantação',
		'URL A' => 'URL A',
		'URL B' => 'URL B',
		'URL C' => 'URL C',
		'Host networks' => 'Redes do host',
		'Host netmask' => 'Máscara de rede do host',
		'Host router' => 'Roteador do host',
		'OOB IP' => 'IP OOB',
		'OOB netmask' => 'Máscara de rede OOB',
		'OOB router' => 'Roteador OOB',
		'HW purchase date' => 'Data de aquisição do HW',
		'HW install date' => 'Data de instalação do HW',
		'HW expiry date' => 'Data de validade do HW',
		'HW decommission date' => 'Data de desativação do HW',
		'Site address A' => 'Endereço do site A',
		'Site address B' => 'Endereço do site B',
		'Site address C' => 'Endereço do site C',
		'Site city' => 'Cidade do site',
		'Site state' => 'Estado do site',
		'Site country' => 'País do site',
		'Site ZIP' => 'CEP do site',
		'Site rack' => 'Rack do site',
		'Site notes' => 'Notas do site',
		'POC 1 name' => 'Nome do contato 1',
		'POC 1 email' => 'E-mail do contato 1',
		'POC 1 phone A' => 'Telefone A do contato 1',
		'POC 1 phone B' => 'Telefone B do contato 1',
		'POC 1 cell' => 'Celular do contato 1',
		'POC 1 screen' => 'Usuário de chat do contato 1',
		'POC 1 notes' => 'Notas do contato 1',
		'POC 2 name' => 'Nome do contato 2',
		'POC 2 email' => 'E-mail do contato 2',
		'POC 2 phone A' => 'Telefone A do contato 2',
		'POC 2 phone B' => 'Telefone B do contato 2',
		'POC 2 cell' => 'Celular do contato 2',
		'POC 2 screen' => 'Usuário de chat do contato 2',
		'POC 2 notes' => 'Notas do contato 2',
	];

	public static function isPortuguese(): bool {
		$data = \CWebUser::$data;
		$lang = is_array($data) ? (string) ($data['lang'] ?? '') : '';

		return str_starts_with($lang, 'pt_');
	}

	public static function t(string $text, ...$args): string {
		$translated = (self::isPortuguese() && isset(self::STRINGS[$text])) ? self::STRINGS[$text] : $text;

		return $args ? vsprintf($translated, $args) : $translated;
	}
}
