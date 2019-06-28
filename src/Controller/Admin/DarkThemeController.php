<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\ExampleModuleMailtheme\Controller\Admin;

use PrestaShop\Module\ExampleModuleMailtheme\DarkThemeSettings;
use PrestaShop\Module\ExampleModuleMailtheme\Form\DarkThemeType;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class DarkThemeController extends FrameworkBundleAdminController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        /** @var DarkThemeSettings $darkThemeSettings */
        $darkThemeSettings = $this->get('prestashop.module.example_module_mailtheme.dark_theme_settings');

        $form = $this->createForm(DarkThemeType::class, $darkThemeSettings->getSettings());

        return $this->render('@Modules/example_module_mailtheme/views/templates/admin/index.html.twig', [
            'enableSidebar' => true,
            'darkThemeForm' => $form->createView(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function saveSettingsAction(Request $request)
    {
        /** @var DarkThemeSettings $darkThemeSettings */
        $darkThemeSettings = $this->get('prestashop.module.example_module_mailtheme.dark_theme_settings');

        $form = $this->createForm(DarkThemeType::class, $darkThemeSettings->getSettings());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $darkThemeSettings->saveSettings($form->getData());

            $this->addFlash('success', $this->trans('Your settings for Dark Theme are saved.', 'Modules.ExampleModuleMailtheme'));
        } else {
            $this->addFlash('error', $this->trans('Your settings for Dark Theme cannot be saved.', 'Modules.ExampleModuleMailtheme'));
        }

        return $this->redirectToRoute('admin_example_module_mailtheme');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetDefaultSettingsAction(Request $request)
    {
        /** @var DarkThemeSettings $darkThemeSettings */
        $darkThemeSettings = $this->get('prestashop.module.example_module_mailtheme.dark_theme_settings');
        $darkThemeSettings->initSettings();

        $this->addFlash('success', $this->trans('The default settings for Dark Theme are reset.', 'Modules.ExampleModuleMailtheme'));

        return $this->redirectToRoute('admin_example_module_mailtheme');
    }
}
