<?php declare(strict_types = 1);

namespace Modules\GetHostInfo;

use APP;
use CController as CAction;
use Zabbix\Core\CModule;

/**
 * Get Host Info (read-only) — Zabbix 7.0 LTS
 *
 * Porte do modulo "get-host-ro" (Mickael Martin / Cyres, framagit) para o
 * padrao MVC do Zabbix 7.0: toda a logica de API fica no controller, a view
 * apenas renderiza com escape. JS/CSS 100% inline (F5 BIG-IP bloqueia estaticos).
 */
class Module extends CModule {

	public function init(): void {
		// Guard umbrella Ferramentas
		if (defined('ZBX_FERRAMENTAS_ACTIVE') && ZBX_FERRAMENTAS_ACTIVE === true) { return; }
		// O acesso ao menu pode falhar em contextos sem UI (CLI, setup).
		// Envolver em try/catch evita derrubar o frontend inteiro.
		try {
			$menu = APP::Component()->get('menu.main');

			$monitoring = $menu->findOrAdd(_('Monitoring'));
			if ($monitoring !== null) {
				$submenu = $monitoring->getSubmenu();

				$item = (new \CMenuItem(_('Host info (read-only)')))
					->setAction('gethostinfo.view');

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
		catch (\Throwable $e) {
			// Silencioso de proposito: modulo carregado sem contexto de menu.
		}
	}

	public function onBeforeAction(CAction $action): void {
	}

	public function onTerminate(CAction $action): void {
	}
}
