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

use PrestaShop\PrestaShop\Core\MailTemplate\Layout\Layout;
use PrestaShop\PrestaShop\Core\MailTemplate\Layout\LayoutInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\Layout\LayoutVariablesBuilderInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailTemplateRendererInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\MailTemplateInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCatalogInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCollectionInterface;
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
                'name' => 'Example Module Email Theme',
                'parent_class_name' => 'AdminMailTheme',
            ],
        ];
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Example Module Email Theme', array(), 'Modules.ExampleModuleMailtheme.Admin');
        $this->description = $this->trans('Example module to deal with a Email theme in PrestaShop.', array(), 'Modules.ExampleModuleMailtheme.Admin');
        $this->secure_key = Tools::encrypt($this->name);

        $this->ps_versions_compliancy = array('min' => '1.7.5.0', 'max' => _PS_VERSION_);
        $this->templateFile = 'module:example_module_mailtheme/views/templates/index.tpl';
        $this->hookList = [
            ThemeCatalogInterface::LIST_MAIL_THEMES_HOOK,
            LayoutVariablesBuilderInterface::BUILD_MAIL_LAYOUT_VARIABLES_HOOK,
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
     * @param array $hookParams
     */
    public function hookActionListMailThemes(array $hookParams)
    {
        if (!isset($hookParams['mailThemes'])) {
            return;
        }

        //Add the module theme called example_module_theme
        /** @var ThemeCollectionInterface $themes */
        $themes = $hookParams['mailThemes'];
        $this->addLayoutToCollection($themes);
        $this->extendOrderConfLayout($themes);
    }

    /**
     * This hook is used to add/remove layout to the theme's collection. In this case
     * we add a layout customized_template linked to this module to each theme.
     *
     * @param ThemeCollectionInterface $themes
     */
    private function addLayoutToCollection(ThemeCollectionInterface $themes)
    {
        /** @var ThemeInterface $theme */
        foreach ($themes as $theme) {
            if (!in_array($theme->getName(), ['classic', 'modern'])) {
                continue;
            }

            $theme->getLayouts()->add(new Layout(
                'customized_template',
                __DIR__ . '/mails/layouts/customized_' . $theme->getName() . '_layout.html.twig',
                '',
                $this->name
            ));
        }
    }

    /**
     * @param ThemeCollectionInterface $themes
     */
    private function extendOrderConfLayout(ThemeCollectionInterface $themes)
    {
        /** @var ThemeInterface $theme */
        foreach ($themes as $theme) {
            if ('modern' !== $theme->getName()) {
                continue;
            }

            $orderConfLayout = null;
            /** @var LayoutInterface $layout */
            foreach ($theme->getLayouts() as $layout) {
                if ('order_conf' === $layout->getName() && empty($layout->getModuleName())) {
                    $orderConfLayout = $layout;
                    break;
                }
            }

            //Replace the layout in the theme
            if (null === $orderConfLayout) {
                return;
            }

            //The layout collection extends from ArrayCollection so it has more feature than it seems..
            $orderIndex = $theme->getLayouts()->indexOf($orderConfLayout);
            $theme->getLayouts()->offsetSet($orderIndex, new Layout(
                $orderConfLayout->getName(),
                __DIR__ . '/mails/layouts/extended_modern_order_conf_layout.html.twig',
                ''
            ));
        }
    }

    /**
     * This hook is used to modify the layout variables. In this cas we add the
     * customMessage variable required by customized_template.
     *
     * @param array $hookParams
     */
    public function hookActionBuildMailLayoutVariables(array $hookParams)
    {
        if (!isset($hookParams['mailLayout'])) {
            return;
        }
        /** @var LayoutInterface $mailLayout */
        $mailLayout = $hookParams['mailLayout'];
        if ($mailLayout->getModuleName() != $this->name) {
            return;
        }

        $hookParams['mailLayoutVariables']['customMessage'] = 'My custom message';
    }

    /**
     * @param array $hookParams
     */
    public function hookActionGetMailLayoutTransformations(array $hookParams)
    {
        if (!isset($hookParams['templateType']) ||
            MailTemplateInterface::HTML_TYPE !== $hookParams['templateType'] ||
            !isset($hookParams['mailLayout']) ||
            !isset($hookParams['layoutTransformations'])) {
            return;
        }

        /** @var LayoutInterface $mailLayout */
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
