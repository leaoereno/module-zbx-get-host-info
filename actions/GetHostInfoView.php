<?php declare(strict_types = 1);

namespace Modules\GetHostInfo\Actions;

use CController as CAction;
use CControllerResponseData;
use CControllerResponseFatal;
use Modules\GetHostInfo\Lang;

/**
 * Controller: gethostinfo.view
 *
 * Faz toda a coleta via API do Zabbix (contexto do usuario logado, portanto
 * respeita permissoes de grupo) e entrega um array $data ja normalizado para
 * a view. Nenhuma chamada de API ou logica de negocio deve viver na view.
 */
class GetHostInfoView extends CAction {

	public function init(): void {
		// Acao somente leitura, sem alteracao de dados -> CSRF nao se aplica.
		$this->disableCsrfValidation();
	}

	protected function checkInput(): bool {
		$fields = [
			'host' => 'string'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(new CControllerResponseFatal());
		}

		return $ret;
	}

	protected function checkPermissions(): bool {
		// Mesmo perfil do modulo original: qualquer usuario logado.
		// A API roda no contexto do usuario, entao ele so enxerga hosts dos
		// grupos que tem permissao de leitura.
		$permit_user_types = [USER_TYPE_ZABBIX_USER, USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN];

		return in_array($this->getUserType(), $permit_user_types, true);
	}

	protected function doAction(): void {
		$search = trim((string)$this->getInput('host', ''));

		$data = [
			'search'         => $search,
			'hosts_datalist' => [],
			'state'          => 'empty',   // empty | multiple | none | found | error
			'info'           => null,
			'error'          => null
		];

		// A lista de autocomplete e cosmetica: se falhar, a busca continua
		// funcionando normalmente, so perde a sugestao de nomes.
		try {
			$data['hosts_datalist'] = $this->getHostsDatalist();
		}
		catch (\Throwable $e) {
			$data['hosts_datalist'] = [];
		}

		if ($search === '') {
			$this->respond($data);
			return;
		}

		try {
			// Resolve o host pelo nome tecnico OU display name.
			$matches = \API::Host()->get([
				'output'      => ['hostid'],
				'filter'      => ['host' => $search, 'name' => $search],
				'searchByAny' => true
			]);
		}
		catch (\Throwable $e) {
			$data['state'] = 'error';
			$data['error'] = $e->getMessage();
			$this->respond($data);
			return;
		}

		if (count($matches) === 0) {
			$data['state'] = 'none';
			$this->respond($data);
			return;
		}

		if (count($matches) > 1) {
			$data['state'] = 'multiple';
			$data['match_count'] = count($matches);
			$this->respond($data);
			return;
		}

		$hostid = $matches[0]['hostid'];

		try {
			$data['state'] = 'found';
			$data['info']  = $this->collectHostInfo($hostid);
		}
		catch (\Throwable $e) {
			// Host pode ter sido removido entre o get() acima e a coleta,
			// ou uma sub-chamada de API falhou (permissao, timeout, etc.).
			// Mostra um card de erro em vez de derrubar a pagina inteira.
			$data['state'] = 'error';
			$data['error'] = $e->getMessage();
			$data['info']  = null;
		}

		$this->respond($data);
	}

	private function respond(array $data): void {
		$data['title'] = Lang::t('Host info (read-only)');
		$this->setResponse(new CControllerResponseData($data));
	}

	/**
	 * Limite de seguranca para o <datalist> de autocomplete. Em ambientes com
	 * dezenas de milhares de hosts, carregar a lista inteira em toda
	 * requisicao infla o HTML desnecessariamente; acima do limite a busca
	 * exata (input livre) continua funcionando normalmente, so o
	 * autocomplete fica incompleto.
	 */
	private const DATALIST_LIMIT = 3000;

	/**
	 * Lista de nomes para o autocomplete (host tecnico + display name).
	 */
	private function getHostsDatalist(): array {
		$hosts = \API::Host()->get([
			'output'    => ['host', 'name'],
			'sortfield' => 'host',
			'limit'     => self::DATALIST_LIMIT
		]);

		$names = [];
		foreach ($hosts as $h) {
			$names[] = $h['host'];
			if (!empty($h['name']) && $h['name'] !== $h['host']) {
				$names[] = $h['name'];
			}
		}

		$names = array_values(array_unique($names));
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);

		return $names;
	}

	/**
	 * Nomes e cores de severidade padrao do Zabbix (fallback quando
	 * settings.get falha ou foi revogado na role do usuario atual).
	 */
	private const DEFAULT_SEVERITY_NAMES = [
		0 => 'Not classified', 1 => 'Information', 2 => 'Warning',
		3 => 'Average', 4 => 'High', 5 => 'Disaster'
	];
	private const DEFAULT_SEVERITY_COLORS = [
		0 => '97AAB3', 1 => '7499FF', 2 => 'FFC859',
		3 => 'FFA059', 4 => 'E97659', 5 => 'E45959'
	];

	private function getSeveritySettings(): array {
		try {
			$settings = \API::Settings()->get([
				'output' => ['severity_color_0', 'severity_color_1', 'severity_color_2',
				             'severity_color_3', 'severity_color_4', 'severity_color_5',
				             'severity_name_0', 'severity_name_1', 'severity_name_2',
				             'severity_name_3', 'severity_name_4', 'severity_name_5']
			]);

			if ($settings) {
				return $settings;
			}
		}
		catch (\Throwable $e) {
			// segue para o fallback abaixo
		}

		$fallback = [];
		foreach (self::DEFAULT_SEVERITY_NAMES as $prio => $name) {
			$fallback['severity_name_'.$prio]  = Lang::t($name);
			$fallback['severity_color_'.$prio] = self::DEFAULT_SEVERITY_COLORS[$prio];
		}

		return $fallback;
	}

	/**
	 * Coleta e normaliza toda a configuracao do host.
	 */
	private function collectHostInfo(string $hostid): array {
		$hosts = \API::Host()->get([
			'hostids'               => [$hostid],
			'output'                => ['hostid', 'host', 'name', 'status', 'description',
			                            'proxyid', 'maintenance_status', 'inventory_mode'],
			'selectHostGroups'      => ['groupid', 'name'],
			'selectInterfaces'      => ['interfaceid', 'type', 'main', 'available', 'error',
			                            'ip', 'dns', 'port', 'useip'],
			'selectInventory'       => 'extend',
			'selectMacros'          => ['hostmacroid', 'macro', 'value', 'description', 'type', 'automatic'],
			'selectParentTemplates' => ['templateid', 'name', 'uuid'],
			'selectTags'            => ['tag', 'value', 'automatic']
		]);

		if (!$hosts) {
			// Host removido/perdeu permissao entre o get() de resolucao e
			// esta coleta. Lanca para o catch generico em doAction() em vez
			// de deixar $host = null cascatear em avisos silenciosos.
			throw new \RuntimeException('Host not found or no longer accessible.');
		}

		$host = $hosts[0];

		// --- Proxy (7.0: host.proxyid + proxy.name) -----------------------
		// !empty() ja cobre '0'/''/null; o proxyid so chega vazio quando o
		// host nao tem proxy atribuido.
		$proxy_name = null;
		if (!empty($host['proxyid'])) {
			$proxies = \API::Proxy()->get([
				'proxyids' => [$host['proxyid']],
				'output'   => ['name']
			]);
			if ($proxies) {
				$proxy_name = $proxies[0]['name'];
			}
		}

		// --- Cores e nomes de severidade ------------------------------------
		// settings.get e liberado para qualquer tipo de usuario por padrao,
		// mas o metodo pode ser revogado numa role customizada. Sem
		// try/catch, isso derrubaria a tela inteira so por causa da
		// severidade das triggers -> cai para a paleta padrao do Zabbix.
		$settings = $this->getSeveritySettings();

		// --- Itens --------------------------------------------------------
		$items_raw = \API::Item()->get([
			'hostids'        => [$hostid],
			'webitems'       => true,
			'templated'      => null,
			'output'         => ['itemid', 'type', 'name', 'key_', 'delay', 'history',
			                     'trends', 'status', 'state', 'description'],
			'selectTriggers' => ['triggerid', 'description'],
			'sortfield'      => 'name'
		]);

		$items = [];
		foreach ($items_raw as $it) {
			$trigger_titles = [];
			foreach (($it['triggers'] ?? []) as $t) {
				$trigger_titles[] = $t['description'];
			}
			$items[] = [
				'name'           => $it['name'],
				'key_'           => $it['key_'],
				'delay'          => $it['delay'],
				'history'        => $it['history'],
				'trends'         => $it['trends'],
				'description'    => $it['description'],
				'status'         => (int)$it['status'],
				'state'          => (int)$it['state'],
				'triggers_count' => count($it['triggers'] ?? []),
				'triggers_list'  => $trigger_titles
			];
		}

		// --- Triggers -----------------------------------------------------
		$triggers_raw = \API::Trigger()->get([
			'hostids'           => [$hostid],
			'output'            => ['triggerid', 'description', 'expression', 'priority',
			                        'status', 'state'],
			'expandExpression'  => true,
			'expandDescription' => true,
			'sortfield'         => ['priority', 'description'],
			'sortorder'         => 'DESC'
		]);

		$triggers = [];
		foreach ($triggers_raw as $tr) {
			$prio = (int)$tr['priority'];
			$triggers[] = [
				'description' => $tr['description'],
				'expression'  => $tr['expression'],
				'priority'    => $prio,
				'sev_name'    => $settings['severity_name_'.$prio] ?? null,
				'sev_color'   => $settings['severity_color_'.$prio] ?? null,
				'status'      => (int)$tr['status'],
				'state'       => (int)$tr['state']
			];
		}

		// --- Inventario (apenas campos preenchidos) -----------------------
		$inventory = [];
		if (!empty($host['inventory']) && is_array($host['inventory'])) {
			foreach ($host['inventory'] as $k => $v) {
				if ($k === 'hostid' || $k === 'inventory_mode') {
					continue;
				}
				if ($v !== null && trim((string)$v) !== '') {
					$inventory[$k] = $v;
				}
			}
		}

		// --- Macros -------------------------------------------------------
		$macros = [];
		foreach (($host['macros'] ?? []) as $m) {
			$macros[] = [
				'macro'       => $m['macro'],
				// Macros do tipo Secret/Vault nao retornam 'value' pela API.
				'value'       => array_key_exists('value', $m) ? $m['value'] : null,
				'is_secret'   => !array_key_exists('value', $m),
				'description' => $m['description'] ?? '',
				'automatic'   => (int)($m['automatic'] ?? 0)
			];
		}

		// --- Interfaces ---------------------------------------------------
		$iface_type = [1 => 'Agent', 2 => 'SNMP', 3 => 'IPMI', 4 => 'JMX'];
		$interfaces = [];
		foreach (($host['interfaces'] ?? []) as $if) {
			$interfaces[] = [
				'type'      => $iface_type[(int)$if['type']] ?? ('#'.$if['type']),
				'ip'        => $if['ip'] ?? '',
				'dns'       => $if['dns'] ?? '',
				'port'      => $if['port'] ?? '',
				'useip'     => (int)($if['useip'] ?? 1),
				'main'      => (int)($if['main'] ?? 0),
				'available' => (int)($if['available'] ?? 0),
				'error'     => $if['error'] ?? ''
			];
		}

		// --- Tags ---------------------------------------------------------
		$tags = [];
		foreach (($host['tags'] ?? []) as $t) {
			$tags[] = ['tag' => $t['tag'], 'value' => $t['value'] ?? ''];
		}

		// --- Groups / Templates -------------------------------------------
		$groups = [];
		foreach (($host['hostgroups'] ?? []) as $g) {
			$groups[] = $g['name'];
		}
		sort($groups, SORT_NATURAL | SORT_FLAG_CASE);

		$templates = [];
		foreach (($host['parentTemplates'] ?? []) as $tpl) {
			$templates[] = ['name' => $tpl['name'], 'uuid' => $tpl['uuid'] ?? ''];
		}

		return [
			'host'               => $host['host'],
			'name'               => $host['name'],
			'status'             => (int)$host['status'],
			'maintenance_status' => (int)$host['maintenance_status'],
			'description'        => $host['description'],
			'proxy'              => $proxy_name,
			'interfaces'         => $interfaces,
			'groups'             => $groups,
			'templates'          => $templates,
			'tags'               => $tags,
			'macros'             => $macros,
			'inventory'          => $inventory,
			'items'              => $items,
			'triggers'           => $triggers
		];
	}
}
