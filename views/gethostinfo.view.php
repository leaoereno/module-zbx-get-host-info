<?php declare(strict_types = 1);
/**
 * View: gethostinfo.view.php
 *
 * SOMENTE renderizacao. Toda a coleta de dados acontece no controller
 * (actions/GetHostInfoView.php). Todo dado externo passa por escape.
 *
 * Paleta/componentes seguem o design system canonico dos modulos
 * (tokens --c-*, .badge, .card, .tbl, .empty) — ver
 * references/ui-design-system.md na skill zabbix-module-dev.
 *
 * $data:
 *   string       search
 *   array        hosts_datalist
 *   string       state         empty|none|multiple|found|error
 *   int          match_count   (quando state=multiple)
 *   string|null  error         (quando state=error)
 *   array|null   info
 */

use Modules\GetHostInfo\Lang;

if (!function_exists('gethostinfo_e')) {
	function gethostinfo_e($v): string {
		return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
	}
}

/** Labels amigaveis (chave EN -> dicionario Lang) para campos de inventario. */
$inv_labels = [
	'type' => 'Type', 'type_full' => 'Type (full)', 'name' => 'Name', 'alias' => 'Alias',
	'os' => 'OS', 'os_full' => 'OS (full)', 'os_short' => 'OS (short)',
	'serialno_a' => 'Serial A', 'serialno_b' => 'Serial B', 'tag' => 'Tag',
	'asset_tag' => 'Asset tag', 'macaddress_a' => 'MAC A', 'macaddress_b' => 'MAC B',
	'hardware' => 'Hardware', 'hardware_full' => 'Hardware (full)',
	'software' => 'Software', 'software_full' => 'Software (full)',
	'software_app_a' => 'Software app A', 'software_app_b' => 'Software app B',
	'software_app_c' => 'Software app C', 'software_app_d' => 'Software app D',
	'software_app_e' => 'Software app E', 'contact' => 'Contact', 'location' => 'Location',
	'location_lat' => 'Latitude', 'location_lon' => 'Longitude', 'notes' => 'Notes',
	'chassis' => 'Chassis', 'model' => 'Model', 'hw_arch' => 'HW architecture',
	'vendor' => 'Vendor', 'contract_number' => 'Contract number',
	'installer_name' => 'Installer', 'deployment_status' => 'Deployment status',
	'url_a' => 'URL A', 'url_b' => 'URL B', 'url_c' => 'URL C',
	'host_networks' => 'Host networks', 'host_netmask' => 'Host netmask',
	'host_router' => 'Host router', 'oob_ip' => 'OOB IP', 'oob_netmask' => 'OOB netmask',
	'oob_router' => 'OOB router', 'date_hw_purchase' => 'HW purchase date',
	'date_hw_install' => 'HW install date', 'date_hw_expiry' => 'HW expiry date',
	'date_hw_decomm' => 'HW decommission date', 'site_address_a' => 'Site address A',
	'site_address_b' => 'Site address B', 'site_address_c' => 'Site address C',
	'site_city' => 'Site city', 'site_state' => 'Site state', 'site_country' => 'Site country',
	'site_zip' => 'Site ZIP', 'site_rack' => 'Site rack', 'site_notes' => 'Site notes',
	'poc_1_name' => 'POC 1 name', 'poc_1_email' => 'POC 1 email',
	'poc_1_phone_a' => 'POC 1 phone A', 'poc_1_phone_b' => 'POC 1 phone B',
	'poc_1_cell' => 'POC 1 cell', 'poc_1_screen' => 'POC 1 screen', 'poc_1_notes' => 'POC 1 notes',
	'poc_2_name' => 'POC 2 name', 'poc_2_email' => 'POC 2 email',
	'poc_2_phone_a' => 'POC 2 phone A', 'poc_2_phone_b' => 'POC 2 phone B',
	'poc_2_cell' => 'POC 2 cell', 'poc_2_screen' => 'POC 2 screen', 'poc_2_notes' => 'POC 2 notes'
];

$search   = (string)($data['search'] ?? '');
$state    = (string)($data['state'] ?? 'empty');
$datalist = $data['hosts_datalist'] ?? [];
$info     = $data['info'] ?? null;
$error    = $data['error'] ?? null;
?>
<style>
/* ====== Get Host Info — tokens canonicos do design system, escopo .ghi ====== */
.ghi { --c-bg:#f4f6f9; --c-card:#fff; --c-border:#dde3ec;
       --c-accent:#1565c0; --c-accent-light:#e8f0fe; --c-accent-hover:#0d47a1;
       --c-text:#1a1f36; --c-muted:#6b7a99;
       --c-success:#1b7e47; --c-success-bg:#e8f5e9;
       --c-danger:#b71c1c;  --c-danger-bg:#fff5f5; --c-danger-border:#f5c6c6;
       --c-warn:#bf6000;    --c-warn-bg:#fff8e1;    --c-warn-border:#ffe082;
       --c-shadow:0 1px 4px rgba(0,0,0,.08);
       --c-shadow-md:0 4px 6px rgba(0,0,0,.05),0 2px 4px rgba(0,0,0,.04);
       font-size: 13px;
       /* respiro entre a sidebar nativa e o conteudo do modulo */
       padding: 10px 18px 24px 24px; }
.ghi, .ghi *, .ghi *::before, .ghi *::after { box-sizing: border-box; }

.ghi .ghi-title { font-size: 19px; font-weight: 800; color: var(--c-text); margin: 2px 0 16px; }

/* Barra de pesquisa: wrapper leve; os controles usam estilo NATIVO do Zabbix
   (CTextBox/CSubmit) para herdar cor, borda, foco e altura do tema. */
.ghi .ghi-searchbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
       margin-bottom: 18px; }
.ghi .ghi-searchbar label { font-weight: 600; color: var(--c-text); }
.ghi .ghi-searchbar input[type="text"] { min-width: 320px; }

/* Empty / warning / error states (componente .empty canonico) */
.ghi .empty { padding: 44px 20px; text-align: center; background: var(--c-card);
       border: 1px solid var(--c-border); border-radius: 10px; box-shadow: var(--c-shadow); }
.ghi .empty-icon { font-size: 38px; display: block; margin-bottom: 12px; line-height: 1; }
.ghi .empty-title { font-size: 15px; font-weight: 700; color: var(--c-text); margin-bottom: 6px; }
.ghi .empty-desc { font-size: 13px; color: var(--c-muted); }
.ghi .empty.warn { background: var(--c-warn-bg); border-color: var(--c-warn-border); }
.ghi .empty.warn .empty-title { color: var(--c-warn); }
.ghi .empty.err { background: var(--c-danger-bg); border-color: var(--c-danger-border); }
.ghi .empty.err .empty-title { color: var(--c-danger); }
.ghi .empty.err .empty-desc { word-break: break-word; }

/* Cabecalho do host */
.ghi .ghi-hosthead { display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
       padding: 16px 18px; border: 1px solid var(--c-border); border-radius: 10px;
       background: var(--c-card); box-shadow: var(--c-shadow); margin-bottom: 18px; }
.ghi .ghi-hosthead h2 { margin: 0; font-size: 20px; font-weight: 700; color: var(--c-text); }
.ghi .ghi-hosthead .ghi-sub { color: var(--c-muted); font-size: 13px; }
.ghi .ghi-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-left: auto; }

/* Badges / status pills (canonico) */
.ghi .badge { display: inline-flex; align-items: center; gap: 3px; padding: 3px 9px;
       border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.ghi .badge-ok   { background: var(--c-success-bg); color: var(--c-success); }
.ghi .badge-warn { background: var(--c-warn-bg);    color: var(--c-warn); }
.ghi .badge-err  { background: var(--c-danger-bg);  color: var(--c-danger); }
.ghi .badge-info { background: var(--c-accent-light); color: var(--c-accent); }
.ghi .badge-gray { background: #f1f5f9; color: #64748b; }
.ghi .badge .num { font-weight: 800; }

/* Grid de cards */
.ghi .ghi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
       gap: 16px; }
.ghi .card { background: var(--c-card); border: 1px solid var(--c-border); border-radius: 10px;
       box-shadow: var(--c-shadow); overflow: hidden; margin: 0; }
.ghi .card.span-all { grid-column: 1 / -1; }
.ghi .card > summary { list-style: none; cursor: pointer;
       display: flex; align-items: center; gap: 10px; padding: 12px 18px;
       background: var(--c-bg); border-bottom: 1px solid var(--c-border);
       font-weight: 700; font-size: 14px; color: var(--c-text); user-select: none; }
.ghi .card > summary::-webkit-details-marker { display: none; }
.ghi .card > summary::before { content: '▸'; color: var(--c-muted); transition: transform .15s; }
.ghi .card[open] > summary::before { transform: rotate(90deg); }
.ghi .card:not([open]) > summary { border-bottom: none; }
.ghi .card .count-pill { margin-left: auto; font-size: 11px; font-weight: 700;
       padding: 2px 9px; border-radius: 999px; background: #fff;
       border: 1px solid var(--c-border); color: var(--c-muted); }
.ghi .card-body { padding: 4px 0 6px; }

/* Tabela chave/valor */
.ghi table.ghi-kv { width: 100%; border-collapse: collapse; }
.ghi table.ghi-kv th { text-align: left; font-weight: 600; color: var(--c-muted); width: 38%;
       padding: 8px 18px; vertical-align: top; white-space: nowrap; }
.ghi table.ghi-kv td { padding: 8px 18px; vertical-align: top; word-break: break-word; color: var(--c-text); }
.ghi table.ghi-kv tr + tr th, .ghi table.ghi-kv tr + tr td { border-top: 1px solid #f1f5f9; }
.ghi table.ghi-kv ul { margin: 0; padding-left: 18px; }
.ghi table.ghi-kv li { margin: 1px 0; }

/* Tabela de dados (itens, triggers, macros) — variante do .tbl canonico */
.ghi .ghi-toolbar { display: flex; justify-content: flex-end; padding: 10px 14px;
       background: var(--c-bg); border-bottom: 1px solid var(--c-border); }
.ghi .ghi-filter { padding: 6px 11px 6px 30px; border: 1px solid var(--c-border);
       border-radius: 7px; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7a99' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='M21 21l-4.3-4.3'/%3E%3C/svg%3E") no-repeat 8px center / 15px;
       color: var(--c-text); min-width: 220px; outline: none; font-size: 13px; }
.ghi .ghi-filter:focus { border-color: var(--c-accent); outline: 2px solid var(--c-accent-light); }
.ghi .ghi-tablewrap { overflow-x: auto; }
.ghi table.ghi-data { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.ghi table.ghi-data thead th { position: sticky; top: 0; text-align: left;
       font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .5px;
       padding: 9px 14px; background: var(--c-bg); color: var(--c-muted);
       border-bottom: 2px solid var(--c-border); cursor: pointer; white-space: nowrap; }
.ghi table.ghi-data thead th[data-nosort] { cursor: default; }
.ghi table.ghi-data thead th .arrow { opacity: .4; font-size: 10px; margin-left: 4px; }
.ghi table.ghi-data thead th.sort-asc .arrow,
.ghi table.ghi-data thead th.sort-desc .arrow { opacity: 1; color: var(--c-accent); }
.ghi table.ghi-data tbody td { padding: 8px 14px; border-bottom: 1px solid #f1f5f9;
       vertical-align: top; word-break: break-word; color: var(--c-text); }
.ghi table.ghi-data tbody tr:hover td { background: #fafbff; }
.ghi table.ghi-data code { font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
       font-size: 12px; color: var(--c-muted); }
.ghi .ghi-empty-row td { text-align: center; color: var(--c-muted); padding: 18px; }

/* Pills de estado */
.ghi .pill { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11px;
       font-weight: 700; }
.ghi .pill.green { color: var(--c-success); background: var(--c-success-bg); }
.ghi .pill.red   { color: var(--c-danger);  background: var(--c-danger-bg); }
.ghi .pill.grey  { color: var(--c-muted);   background: #f1f5f9; }
.ghi .sev { display: inline-block; padding: 2px 10px; border-radius: 6px; font-weight: 700;
       font-size: 11px; border-left: 4px solid currentColor; }
.ghi .ghi-tagchip { display: inline-block; padding: 2px 8px; margin: 2px; border-radius: 5px;
       font-size: 11px; background: var(--c-accent-light); color: var(--c-accent);
       border: 1px solid var(--c-border); }
.ghi .secret { color: var(--c-muted); font-style: italic; }
.ghi .help { cursor: help; border-bottom: 1px dotted currentColor; }
.ghi .trig-count { color: var(--c-accent); font-weight: 700; }
</style>

<div class="ghi">

<div class="ghi-title">📋 <?= Lang::t('Host info (read-only)') ?></div>

<?php
	// Datalist (autocomplete) com todos os hosts visiveis ao usuario.
	$datalist_options = '';
	foreach ($datalist as $name) {
		$datalist_options .= '<option value="'.gethostinfo_e($name).'"></option>';
	}

	// Controles NATIVOS do Zabbix -> herdam exibicao/cor/foco do tema.
	$host_box = (new CTextBox('host', $search))
		->setId('ghi-host')
		->setAttribute('list', 'ghi-hostlist')
		->setAttribute('placeholder', Lang::t('Technical name or display name...'))
		->setAttribute('autocomplete', 'off');

	$search_form = (new CForm('get', 'zabbix.php'))
		->cleanItems()
		->addItem(new CVar('action', 'gethostinfo.view'))
		->addItem(
			(new CDiv([
				new CLabel(Lang::t('Host'), 'ghi-host'),
				$host_box,
				new CSubmit('', Lang::t('Search host'))
			]))->addClass('ghi-searchbar')
		);

	$search_form->show();
?>
	<datalist id="ghi-hostlist"><?= $datalist_options ?></datalist>


<?php if ($state === 'error'): ?>
	<div class="empty err">
		<span class="empty-icon">⚠️</span>
		<div class="empty-title"><?= Lang::t('Could not load host data') ?></div>
		<div class="empty-desc"><?= gethostinfo_e($error ?? Lang::t('Unknown error. Try again shortly.')) ?></div>
	</div>
<?php elseif ($state === 'multiple'): ?>
	<div class="empty warn">
		<span class="empty-icon">👥</span>
		<div class="empty-title"><?= Lang::t('More than one host found') ?></div>
		<div class="empty-desc">
			<?= Lang::t('The pattern "%1$s" returned %2$d hosts. Refine your search and try again.',
				gethostinfo_e($search), (int)($data['match_count'] ?? 0)) ?>
		</div>
	</div>
<?php elseif ($state === 'none'): ?>
	<div class="empty warn">
		<span class="empty-icon">🔍</span>
		<div class="empty-title"><?= Lang::t('No host found') ?></div>
		<div class="empty-desc">
			<?= Lang::t('The pattern "%s" returned no hosts. Check your search and try again.',
				gethostinfo_e($search)) ?>
		</div>
	</div>
<?php elseif ($state === 'empty'): ?>
	<div class="empty">
		<span class="empty-icon">🔍</span>
		<div class="empty-title"><?= Lang::t('Search for a host') ?></div>
		<div class="empty-desc"><?= Lang::t('Type the host\'s technical or display name above and click "Search host".') ?></div>
	</div>
<?php elseif ($state === 'found' && $info !== null): ?>

	<?php
	$st_ok   = $info['status'] === 0;
	$maint   = $info['maintenance_status'] === 1;
	?>
	<div class="ghi-hosthead">
		<div>
			<h2><?= gethostinfo_e($info['name'] !== '' ? $info['name'] : $info['host']) ?></h2>
			<div class="ghi-sub"><?= gethostinfo_e($info['host']) ?></div>
		</div>
		<div class="ghi-badges">
			<span class="badge <?= $st_ok ? 'badge-ok' : 'badge-err' ?>">
				<?= $st_ok ? Lang::t('Monitored') : Lang::t('Not monitored') ?>
			</span>
<?php if ($maint): ?>
			<span class="badge badge-warn"><?= Lang::t('In maintenance') ?></span>
<?php endif; ?>
			<span class="badge badge-gray"><?= Lang::t('Proxy') ?>:&nbsp;<?= gethostinfo_e($info['proxy'] ?? Lang::t('none')) ?></span>
			<span class="badge badge-info"><span class="num"><?= count($info['items']) ?></span>&nbsp;<?= Lang::t('items') ?></span>
			<span class="badge badge-info"><span class="num"><?= count($info['triggers']) ?></span>&nbsp;<?= Lang::t('triggers') ?></span>
			<span class="badge badge-info"><span class="num"><?= count($info['macros']) ?></span>&nbsp;<?= Lang::t('macros') ?></span>
		</div>
	</div>

	<div class="ghi-grid">

		<!-- ====== Geral ====== -->
		<details class="card" open>
			<summary><?= Lang::t('General') ?></summary>
			<div class="card-body">
				<table class="ghi-kv">
					<tr><th><?= Lang::t('Host name') ?></th><td><?= gethostinfo_e($info['host']) ?></td></tr>
					<tr><th><?= Lang::t('Display name') ?></th><td><?= gethostinfo_e($info['name']) ?></td></tr>
					<tr><th><?= Lang::t('Status') ?></th><td>
						<span class="pill <?= $st_ok ? 'green' : 'red' ?>">
							<?= $st_ok ? Lang::t('Monitored') : Lang::t('Not monitored') ?></span>
					</td></tr>
					<tr><th><?= Lang::t('Maintenance') ?></th><td>
						<?= $maint ? '<span class="pill red">'.Lang::t('In maintenance').'</span>'
						           : '<span class="pill grey">'.Lang::t('None').'</span>' ?>
					</td></tr>
					<tr><th><?= Lang::t('Proxy') ?></th><td><?= gethostinfo_e($info['proxy'] ?? Lang::t('No proxy')) ?></td></tr>
					<tr><th><?= Lang::t('Description') ?></th><td><?= nl2br(gethostinfo_e($info['description'])) ?></td></tr>
					<tr><th><?= Lang::t('Interfaces') ?></th><td>
<?php if ($info['interfaces']): ?>
						<ul>
<?php foreach ($info['interfaces'] as $if):
	$addr = $if['useip'] === 1 ? $if['ip'] : ($if['dns'] !== '' ? $if['dns'] : $if['ip']);
	$avail_cls = $if['available'] === 1 ? 'green' : ($if['available'] === 2 ? 'red' : 'grey'); ?>
							<li>
								<span class="pill <?= $avail_cls ?>"><?= gethostinfo_e($if['type']) ?></span>
								<?= gethostinfo_e($addr) ?><?= $if['port'] !== '' ? ':'.gethostinfo_e($if['port']) : '' ?>
<?php if ($if['error'] !== ''): ?>
								<span class="pill red help" title="<?= gethostinfo_e($if['error']) ?>">!</span>
<?php endif; ?>
							</li>
<?php endforeach; ?>
						</ul>
<?php else: ?>
						<span class="secret"><?= Lang::t('No interfaces') ?></span>
<?php endif; ?>
					</td></tr>
					<tr><th><?= Lang::t('Groups') ?></th><td>
<?php if ($info['groups']): ?>
						<ul>
<?php foreach ($info['groups'] as $g): ?>
							<li><?= gethostinfo_e($g) ?></li>
<?php endforeach; ?>
						</ul>
<?php else: ?><span class="secret">&mdash;</span><?php endif; ?>
					</td></tr>
					<tr><th><?= Lang::t('Templates') ?></th><td>
<?php if ($info['templates']): ?>
						<ul>
<?php foreach ($info['templates'] as $tpl): ?>
							<li><span class="help" title="UUID: <?= gethostinfo_e($tpl['uuid']) ?>"><?= gethostinfo_e($tpl['name']) ?></span></li>
<?php endforeach; ?>
						</ul>
<?php else: ?><span class="secret">&mdash;</span><?php endif; ?>
					</td></tr>
					<tr><th><?= Lang::t('Tags') ?></th><td>
<?php if ($info['tags']): ?>
<?php foreach ($info['tags'] as $t): ?>
						<span class="ghi-tagchip"><?= gethostinfo_e($t['tag']) ?><?= $t['value'] !== '' ? ': '.gethostinfo_e($t['value']) : '' ?></span>
<?php endforeach; ?>
<?php else: ?><span class="secret">&mdash;</span><?php endif; ?>
					</td></tr>
				</table>
			</div>
		</details>

		<!-- ====== Macros ====== -->
		<details class="card" open>
			<summary><?= Lang::t('Macros') ?><span class="count-pill"><?= count($info['macros']) ?></span></summary>
			<div class="card-body">
				<div class="ghi-toolbar">
					<input class="ghi-filter" type="text" data-filter="tbl-macros"
					       placeholder="<?= Lang::t('Filter macros...') ?>">
				</div>
				<div class="ghi-tablewrap">
					<table class="ghi-data" id="tbl-macros" data-sortable>
						<thead><tr>
							<th><?= Lang::t('Macro') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('Value') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('Description') ?> <span class="arrow">↕</span></th>
						</tr></thead>
						<tbody>
<?php foreach ($info['macros'] as $m): ?>
							<tr>
								<td><code><?= gethostinfo_e($m['macro']) ?></code></td>
								<td><?= $m['is_secret']
									? '<span class="secret">*** '.Lang::t('secret').' ***</span>'
									: '<code>'.gethostinfo_e($m['value']).'</code>' ?></td>
								<td><?= gethostinfo_e($m['description']) ?></td>
							</tr>
<?php endforeach; ?>
<?php if (!$info['macros']): ?>
							<tr class="ghi-empty-row"><td colspan="3"><?= Lang::t('No macros') ?></td></tr>
<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</details>

		<!-- ====== Inventario ====== -->
		<details class="card">
			<summary><?= Lang::t('Inventory') ?><span class="count-pill"><?= count($info['inventory']) ?></span></summary>
			<div class="card-body">
<?php if ($info['inventory']): ?>
				<table class="ghi-kv">
<?php foreach ($info['inventory'] as $k => $v):
	$label = Lang::t($inv_labels[$k] ?? ucfirst(str_replace('_', ' ', $k))); ?>
					<tr><th><?= gethostinfo_e($label) ?></th><td><?= nl2br(gethostinfo_e($v)) ?></td></tr>
<?php endforeach; ?>
				</table>
<?php else: ?>
				<div style="padding:16px;" class="secret"><?= Lang::t('Empty or disabled inventory.') ?></div>
<?php endif; ?>
			</div>
		</details>

		<!-- ====== Itens ====== -->
		<details class="card span-all" open>
			<summary><?= Lang::t('Items') ?><span class="count-pill"><?= count($info['items']) ?></span></summary>
			<div class="card-body">
				<div class="ghi-toolbar">
					<input class="ghi-filter" type="text" data-filter="tbl-items"
					       placeholder="<?= Lang::t('Filter items...') ?>">
				</div>
				<div class="ghi-tablewrap">
					<table class="ghi-data" id="tbl-items" data-sortable>
						<thead><tr>
							<th><?= Lang::t('Name') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('Key') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('Delay') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('History') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('Trends') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('Status') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('State') ?> <span class="arrow">↕</span></th>
							<th data-nosort><?= Lang::t('Triggers') ?></th>
						</tr></thead>
						<tbody>
<?php foreach ($info['items'] as $it): ?>
							<tr>
								<td><?= gethostinfo_e($it['name']) ?><?= $it['description'] !== ''
									? ' <span class="help" title="'.gethostinfo_e($it['description']).'">ⓘ</span>' : '' ?></td>
								<td><code><?= gethostinfo_e($it['key_']) ?></code></td>
								<td><?= gethostinfo_e($it['delay']) ?></td>
								<td><?= gethostinfo_e($it['history']) ?></td>
								<td><?= gethostinfo_e($it['trends']) ?></td>
								<td><span class="pill <?= $it['status'] === 0 ? 'green' : 'red' ?>">
									<?= $it['status'] === 0 ? Lang::t('Enabled') : Lang::t('Disabled') ?></span></td>
								<td><span class="pill <?= $it['state'] === 0 ? 'green' : 'red' ?>">
									<?= $it['state'] === 0 ? Lang::t('Normal') : Lang::t('Not supported') ?></span></td>
								<td><?php if ($it['triggers_count'] > 0): ?>
									<span class="trig-count help" title="<?= gethostinfo_e(implode("\n", $it['triggers_list'])) ?>"><?= (int)$it['triggers_count'] ?></span>
								<?php else: ?><span class="secret">0</span><?php endif; ?></td>
							</tr>
<?php endforeach; ?>
<?php if (!$info['items']): ?>
							<tr class="ghi-empty-row"><td colspan="8"><?= Lang::t('No items') ?></td></tr>
<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</details>

		<!-- ====== Triggers ====== -->
		<details class="card span-all" open>
			<summary><?= Lang::t('Triggers') ?><span class="count-pill"><?= count($info['triggers']) ?></span></summary>
			<div class="card-body">
				<div class="ghi-toolbar">
					<input class="ghi-filter" type="text" data-filter="tbl-triggers"
					       placeholder="<?= Lang::t('Filter triggers...') ?>">
				</div>
				<div class="ghi-tablewrap">
					<table class="ghi-data" id="tbl-triggers" data-sortable>
						<thead><tr>
							<th><?= Lang::t('Name') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('Severity') ?> <span class="arrow">↕</span></th>
							<th data-nosort><?= Lang::t('Expression (expanded)') ?></th>
							<th><?= Lang::t('Status') ?> <span class="arrow">↕</span></th>
							<th><?= Lang::t('State') ?> <span class="arrow">↕</span></th>
						</tr></thead>
						<tbody>
<?php foreach ($info['triggers'] as $tr):
	$sev_color = $tr['sev_color'] ? '#'.preg_replace('/[^0-9A-Fa-f]/', '', $tr['sev_color']) : '#888';
	$sev_name  = $tr['sev_name'] ?? ('P'.$tr['priority']); ?>
							<tr data-sortkey-1="<?= (int)$tr['priority'] ?>">
								<td><?= gethostinfo_e($tr['description']) ?></td>
								<td><span class="sev" style="color: <?= gethostinfo_e($sev_color) ?>; background: <?= gethostinfo_e($sev_color) ?>22;"><?= gethostinfo_e($sev_name) ?></span></td>
								<td><code><?= gethostinfo_e($tr['expression']) ?></code></td>
								<td><span class="pill <?= $tr['status'] === 0 ? 'green' : 'red' ?>">
									<?= $tr['status'] === 0 ? Lang::t('Enabled') : Lang::t('Disabled') ?></span></td>
								<td><span class="pill <?= $tr['state'] === 0 ? 'green' : 'red' ?>">
									<?= $tr['state'] === 0 ? Lang::t('Normal') : Lang::t('Unknown') ?></span></td>
							</tr>
<?php endforeach; ?>
<?php if (!$info['triggers']): ?>
							<tr class="ghi-empty-row"><td colspan="5"><?= Lang::t('No triggers') ?></td></tr>
<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</details>

	</div><!-- /ghi-grid -->
<?php endif; ?>
</div><!-- /ghi -->

<script>
/* Get Host Info — filtro + ordenacao client-side, sem dependencias.
   F5 bloqueia .js estaticos, entao tudo inline. */
(function () {
	'use strict';

	// Escopo .ghi (unico container renderizado por esta view).
	var scope = document.querySelector('.ghi') || document;

	// ---- Filtro por tabela ----
	scope.querySelectorAll('.ghi-filter').forEach(function (inp) {
		inp.addEventListener('input', function () {
			var table = document.getElementById(inp.getAttribute('data-filter'));
			if (!table) return;
			var q = inp.value.trim().toLowerCase();
			table.querySelectorAll('tbody tr').forEach(function (tr) {
				if (tr.classList.contains('ghi-empty-row')) return;
				tr.style.display = (tr.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
			});
		});
	});

	// ---- Ordenacao por coluna ----
	scope.querySelectorAll('table.ghi-data[data-sortable]').forEach(function (table) {
		var ths = table.querySelectorAll('thead th');
		ths.forEach(function (th, idx) {
			if (th.hasAttribute('data-nosort')) return;
			th.addEventListener('click', function () {
				var asc = !th.classList.contains('sort-asc');
				ths.forEach(function (h) { h.classList.remove('sort-asc', 'sort-desc'); });
				th.classList.add(asc ? 'sort-asc' : 'sort-desc');

				var tbody = table.querySelector('tbody');
				var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'))
					.filter(function (r) { return !r.classList.contains('ghi-empty-row'); });

				rows.sort(function (a, b) {
					var va = sortVal(a, idx), vb = sortVal(b, idx);
					if (va < vb) return asc ? -1 : 1;
					if (va > vb) return asc ? 1 : -1;
					return 0;
				});
				rows.forEach(function (r) { tbody.appendChild(r); });
			});
		});
	});

	function sortVal(tr, idx) {
		// Chave explicita tem prioridade (ex.: severidade ordena por numero).
		var ov = tr.getAttribute('data-sortkey-' + idx);
		if (ov !== null) return parseFloat(ov);
		var cell = tr.children[idx];
		var txt = cell ? cell.textContent.trim() : '';
		var num = parseFloat(txt.replace(',', '.'));
		return (!isNaN(num) && /^[\d.,\s%smhd-]+$/.test(txt)) ? num : txt.toLowerCase();
	}
})();
</script>
