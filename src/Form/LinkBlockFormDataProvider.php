<?php
/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
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
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\LinkList\Form;

use PrestaShop\Module\LinkList\Model\LinkBlock;
use PrestaShop\Module\LinkList\Repository\LinkBlockRepository;
use PrestaShop\PrestaShop\Adapter\Module\Module;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

/**
 * Class LinkBlockFormDataProvider
 * @package PrestaShop\Module\LinkList\Form
 */
class LinkBlockFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var int|null
     */
    private $idLinkBlock;

    /**
     * @var LinkBlockRepository
     */
    private $repository;

    /**
     * @var array
     */
    private $languages;

    /**
     * LinkBlockFormDataProvider constructor.
     * @param LinkBlockRepository $repository
     * @param array               $languages
     */
    public function __construct(
        LinkBlockRepository $repository,
        array $languages
    ) {
        $this->repository = $repository;
        $this->languages = $languages;
    }

    /**
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getData()
    {
        if (null === $this->idLinkBlock) {
            return [];
        }

        $linkBlock = new LinkBlock($this->idLinkBlock);

        $arrayLinkBlock = (array) $linkBlock;

        return ['link_block' => [
            'id_link_block' => $arrayLinkBlock['id_link_block'],
            'block_name' => $arrayLinkBlock['name'],
            'id_hook' => $arrayLinkBlock['id_hook'],
            'cms' => $arrayLinkBlock['content']['cms'],
            'product' => $arrayLinkBlock['content']['product'],
            'static' => $arrayLinkBlock['content']['static'],
        ]];
    }

    /**
     * @param array $data
     * @return array
     * @throws \PrestaShop\PrestaShop\Adapter\Entity\PrestaShopDatabaseException
     */
    public function setData(array $data)
    {
        $linkBlock = $data['link_block'];
        $errors = $this->validateLinkBlock($linkBlock);
        if (!empty($errors)) {
            return $errors;
        }

        $linkBlockId = $this->repository->createLinkBlock(
            $linkBlock['block_name'],
            $linkBlock['id_hook'],
            $linkBlock['cms'],
            $linkBlock['static'],
            $linkBlock['product'],
            array()
        );
        $this->setIdLinkBlock($linkBlockId);

        return [];
    }

    /**
     * @return int
     */
    public function getIdLinkBlock()
    {
        return $this->idLinkBlock;
    }

    /**
     * @param int $idLinkBlock
     * @return LinkBlockFormDataProvider
     */
    public function setIdLinkBlock($idLinkBlock)
    {
        $this->idLinkBlock = $idLinkBlock;

        return $this;
    }

    private function validateLinkBlock(array $data)
    {
        $checkAllLanguages = true;
        $errors = [];
        if (!isset($data['id_hook'])) {
            $errors[] = [
                'key' => "Missing id_hook",
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [],
            ];
        }
        if (!isset($data['block_name'])) {
            $errors[] = [
                'key' => "Missing block_name",
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [],
            ];
        } elseif ($checkAllLanguages) {
            foreach ($this->languages as $language) {
                if (
                    !isset($data['block_name'][$language['id_lang']]) ||
                    empty($data['block_name'][$language['id_lang']])
                ) {
                    $errors[] = [
                        'key' => "Missing block_name value for language %s",
                        'domain' => 'Admin.Catalog.Notification',
                        'parameters' => [$language['iso_code']],
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * Register the selected hook to this module if it was not registered yet
     * @param int $hookId
     * @throws \PrestaShopException
     */
    private function updateHook($hookId)
    {
        $hookName = \Hook::getNameById($hookId);
        $module = \Module::getModuleIdByName('ps_linklist');
        if (\Hook::isModuleRegisteredOnHook($module, $hookName, $this->getContext()->shop->id)) {
            \Hook::registerHook($module, $hookName);
        }
    }

    /**
     * Clears the module cache
     */
    private function clearModuleCache()
    {
        $module = \Module::getModuleIdByName('ps_linklist');
        $module->_clearCache($module->templateFile);
    }
}
