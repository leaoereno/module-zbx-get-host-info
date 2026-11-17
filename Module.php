<?php declare(strict_types = 1);

namespace Modules\GetHostInfo;

use APP;
use CController as CAction;
use CMenuItem;
use Zabbix\Core\CModule;

/**
 * Get Host Info (read-only) — Zabbix 7.0 LTS
 *
 * Porte do modulo "get-host-ro" (Mickael Martin / Cyres, framagit) para o
 * padrao MVC do Zabbix 7.0: toda a logica de API fica no controller, a view
 * apenas renderiza com escape. JS/CSS 100% inline (F5 BIG-IP bloqueia estaticos).
 *
 * Nome do item de menu e todas as strings da view acompanham o idioma
 * escolhido pelo usuario no Zabbix (ver Lang.php): em EN o modulo continua
 * "Host info (read-only)"; em PT-BR passa a se chamar "Ciclo de vida do Host".
 */
class Module extends CModule {

	public function init(): void {
		// Guard umbrella Ferramentas
		if (defined('ZBX_FERRAMENTAS_ACTIVE') && ZBX_FERRAMENTAS_ACTIVE === true) { return; }
		// O acesso ao menu pode falhar em contextos sem UI (CLI, setup).
		// Envolver em try/catch evita derrubar o frontend inteiro.
		try {
			$menu = APP::Component()->get('menu.main');

			$item = (new CMenuItem(Lang::t('Host info (read-only)')))
				->setAction('gethostinfo.view');

			// Se outro modulo ja tiver criado o menu de topo "SRE", o item se
			// encaixa dentro dele (SRE > Troubleshooting/Solucao de problemas)
			// em vez de ficar solto em Monitoring. Instalado sozinho, sem o
			// menu SRE, cai no comportamento padrao (Monitoring/Monitoramento).
			$sre_menu = $menu->find('SRE');

			if ($sre_menu !== null) {
				// Tenta reaproveitar o submenu "Troubleshooting" existente em
				// qualquer um dos dois idiomas antes de criar um novo — assim
				// nao duplica o submenu se outro modulo de SRE ja tiver criado
				// o seu no outro idioma.
				$troubleshooting = $sre_menu->getSubmenu()->find('Troubleshooting')
					?? $sre_menu->getSubmenu()->find('Solução de problemas')
					?? $sre_menu->getSubmenu()->findOrAdd(Lang::t('Troubleshooting'));

				$troubleshooting->getSubmenu()->add($item);
			}
			else {
				$monitoring = $menu->findOrAdd(_('Monitoring'));
				if ($monitoring !== null) {
					$submenu = $monitoring->getSubmenu();

					// insertAfter pode nao achar a ancora dependendo da versao/layout;
					// nesse caso caimos no add() simples.
					try {
						$submenu->insertAfter(_('Hosts'), $item);
					}
					catch (\Throwable $e) {
						$submenu->add($item);
					}
				}
			}
		}
		catch (\Throwable $e) {
			// Silencioso de proposito: modulo carregado sem contexto de menu.
		}
	}

	public function onBeforeAction(CAction $action): void {
	}

	public function onTerminate(CAction $action): void {
	}
}
