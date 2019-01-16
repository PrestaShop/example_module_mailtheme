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

if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}
require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\PrestaShop\Core\MailTemplate\MailTemplateRendererInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailTemplateInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailLayoutCatalogInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailLayoutFolderCatalog;
use PrestaShop\PrestaShop\Core\MailTemplate\MailLayoutVariablesBuilderInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailLayoutCollectionInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailLayout;
use PrestaShop\PrestaShop\Core\MailTemplate\MailLayoutInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailThemeCollectionInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailTheme;
use PrestaShop\PrestaShop\Core\MailTemplate\Transformation\TransformationCollectionInterface;
use PrestaShop\Module\ExampleModuleMailtheme\MailTemplate\Transformation\CustomMessageColorTransformation;

class example_module_mailtheme extends Module
{
    /** @var array */
    private $hookList;

    public function __construct()
    {
        $this->name = 'example_module_mailtheme';
        $this->author = 'PrestaShop';
        $this->version = '1.0.0';
        $this->need_instance = 0;
        $this->tabs = [
            [
                'class_name' => 'ExampleModuleMailtheme',
                'visible' => true,
                'name' => 'Example Module Mail Theme',
                'parent_class_name' => 'AdminParentThemes',
            ],
        ];
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Example Module Mail Theme', array(), 'Modules.ExampleModuleMailtheme.Admin');
        $this->description = $this->trans('Example module to add a Mail theme to PrestaShop.', array(), 'Modules.ExampleModuleMailtheme.Admin');
        $this->secure_key = Tools::encrypt($this->name);

        $this->ps_versions_compliancy = array('min' => '1.7.5.0', 'max' => _PS_VERSION_);
        $this->templateFile = 'module:example_module_mailtheme/views/templates/index.tpl';
        $this->hookList = [
            MailLayoutCatalogInterface::LIST_MAIL_THEMES_HOOK,
            MailLayoutFolderCatalog::GET_MAIL_THEME_FOLDER_HOOK,
            MailLayoutCatalogInterface::LIST_MAIL_THEME_LAYOUTS_HOOK,
            MailLayoutVariablesBuilderInterface::BUILD_LAYOUT_VARIABLES_HOOK,
            MailTemplateRendererInterface::GET_MAIL_LAYOUT_TRANSFORMATIONS,
        ];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHooks()
        ;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->unregisterHooks()
        ;
    }

    public function enable($force_all = false)
    {
        return parent::enable($force_all)
            && $this->registerHooks()
        ;
    }

    public function disable($force_all = false)
    {
        return parent::disable($force_all)
            && $this->unregisterHooks()
        ;
    }

    public function getContent()
    {
        //This controller actually does not exist, it is used in the tab
        //and is accessible thanks to routing settings with _legacy_link
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('ExampleModuleMailtheme')
        );
    }

    /**
     * This hook is used to add a custom module theme called example_module_theme
     *
     * @param array $hookParams
     */
    public function hookActionListMailThemes(array $hookParams)
    {
        if (!isset($hookParams['mailThemes'])) {
            return;
        }

        //Add the module theme called example_module_theme
        /** @var MailThemeCollectionInterface $themes */
        $themes = $hookParams['mailThemes'];
        $themes->add(new MailTheme($this->name));
    }

    /**
     * This hook is used to modify the folder of a theme. In this particular
     * case we set our example_module_theme theme's folder on the same path
     * of the classic one. It is for test purposes but it could be a simple
     * way to "extend" a theme.
     *
     * @param array $hookParams
     */
    public function hookActionGetMailThemeFolder(array $hookParams)
    {
        if (!isset($hookParams['mailTheme']) || $this->name !== $hookParams['mailTheme']) {
            return;
        }

        $hookParams['mailThemeFolder'] = preg_replace(
            sprintf('/%s$/', $this->name),
            'classic',
            $hookParams['mailThemeFolder']
        );
    }

    /**
     * This hook is used to add/remove layout to the theme's collection. In this case
     * we add a layout customized_template linked to this module.
     *
     * @param array $hookParams
     */
    public function hookActionListMailThemeLayouts(array $hookParams)
    {
        if (!isset($hookParams['mailTheme']) || $this->name !== $hookParams['mailTheme']) {
            return;
        }

        /** @var MailLayoutCollectionInterface $layouts */
        $layouts = $hookParams['mailThemeLayouts'];
        $layouts->add(new MailLayout(
            'customized_template',
            __DIR__ . '/mails/templates/customized_template.html.twig',
            '',
            $this->name
        ));
    }

    /**
     * This hook is used to modify the layout variables. In this cas we add the
     * customMessage variable required by customized_template.
     *
     * @param array $hookParams
     */
    public function hookActionBuildLayoutVariables(array $hookParams)
    {
        if (!isset($hookParams['mailLayout'])) {
            return;
        }
        /** @var MailLayoutInterface $mailLayout */
        $mailLayout = $hookParams['mailLayout'];
        if ($mailLayout->getModuleName() != $this->name) {
            return;
        }

        $hookParams['mailLayoutVariables']['customMessage'] = 'My custom message';
    }

    public function hookActionGetMailLayoutTransformations(array $hookParams)
    {
        if (!isset($hookParams['templateType']) ||
            MailTemplateInterface::HTML_TYPE !== $hookParams['templateType'] ||
            !isset($hookParams['mailLayout']) ||
            !isset($hookParams['layoutTransformations'])) {
            return;
        }

        /** @var MailLayoutInterface $mailLayout */
        $mailLayout = $hookParams['mailLayout'];
        if ($mailLayout->getModuleName() != $this->name) {
            return;
        }

        /** @var TransformationCollectionInterface $transformations */
        $transformations = $hookParams['layoutTransformations'];
        $transformations->add(new CustomMessageColorTransformation('#FF0000'));
    }

    /**
     * @return bool
     */
    private function registerHooks()
    {
        foreach ($this->hookList as $hookName) {
            if (!$this->registerHook($hookName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    private function unregisterHooks()
    {
        foreach ($this->hookList as $hookName) {
            if (!$this->unregisterHook($hookName)) {
                return false;
            }
        }

        return true;
    }
}
