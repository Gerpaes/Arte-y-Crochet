<?php
/**
 * Adds product photo and stock columns to the Catalog > Monitoring product lists.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ImageColumn;
use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;

class MonitoringImage extends Module
{
    private const GRIDS = [
        'NoQtyProductWithCombination' => 'no_qty_product_with_combination',
        'NoQtyProductWithoutCombination' => 'no_qty_product_without_combination',
        'DisabledProduct' => 'disabled_product',
        'ProductWithoutImage' => 'product_without_image',
        'ProductWithoutDescription' => 'product_without_description',
        'ProductWithoutPrice' => 'product_without_price',
    ];

    public function __construct()
    {
        $this->name = 'monitoringimage';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Arte Crochet';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Monitoring product photo', [], 'Modules.Monitoringimage.Admin');
        $this->description = $this->trans('Adds photo and stock columns to the product lists in Catalog > Monitoring.', [], 'Modules.Monitoringimage.Admin');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook($this->getHookNames())
            && $this->registerHook('displayBackOfficeHeader');
    }

    private function getHookNames(): array
    {
        $hooks = [];
        foreach (array_keys(self::GRIDS) as $camel) {
            $hooks[] = 'action' . $camel . 'GridDefinitionModifier';
            $hooks[] = 'action' . $camel . 'GridDataModifier';
        }

        return $hooks;
    }

    public function hookActionNoQtyProductWithCombinationGridDefinitionModifier(array $params)
    {
        $this->addColumn($params);
    }

    public function hookActionNoQtyProductWithCombinationGridDataModifier(array &$params)
    {
        $this->addExtraData($params);
    }

    public function hookActionNoQtyProductWithoutCombinationGridDefinitionModifier(array $params)
    {
        $this->addColumn($params);
    }

    public function hookActionNoQtyProductWithoutCombinationGridDataModifier(array &$params)
    {
        $this->addExtraData($params);
    }

    public function hookActionDisabledProductGridDefinitionModifier(array $params)
    {
        $this->addColumn($params);
    }

    public function hookActionDisabledProductGridDataModifier(array &$params)
    {
        $this->addExtraData($params);
    }

    public function hookActionProductWithoutImageGridDefinitionModifier(array $params)
    {
        $this->addColumn($params);
    }

    public function hookActionProductWithoutImageGridDataModifier(array &$params)
    {
        $this->addExtraData($params);
    }

    public function hookActionProductWithoutDescriptionGridDefinitionModifier(array $params)
    {
        $this->addColumn($params);
    }

    public function hookActionProductWithoutDescriptionGridDataModifier(array &$params)
    {
        $this->addExtraData($params);
    }

    public function hookActionProductWithoutPriceGridDefinitionModifier(array $params)
    {
        $this->addColumn($params);
    }

    public function hookActionProductWithoutPriceGridDataModifier(array &$params)
    {
        $this->addExtraData($params);
    }

    private function addColumn(array $params): void
    {
        $definition = $params['definition'];
        $columns = $definition->getColumns();
        $columns->addAfter(
            'id_product',
            (new ImageColumn('image'))
                ->setName($this->context->language->iso_code === 'es' ? 'Foto' : 'Photo')
                ->setOptions([
                    'src_field' => 'image',
                    'alt_field' => 'name',
                    'clickable' => false,
                ])
        );
        $columns->addAfter(
            'name',
            (new DataColumn('stock'))
                ->setName('Stock')
                ->setOptions(['field' => 'stock'])
        );
    }

    private function addExtraData(array &$params): void
    {
        /** @var GridData $data */
        $data = $params['data'];
        $placeholder = __PS_BASE_URI__ . 'img/p/' . $this->context->language->iso_code . '-default-home_default.jpg';

        $records = [];
        foreach ($data->getRecords() as $record) {
            $cover = Image::getCover((int) $record['id_product']);
            $record['image'] = $cover
                ? __PS_BASE_URI__ . 'img/p/' . Image::getImgFolderStatic($cover['id_image']) . $cover['id_image'] . '-home_default.jpg'
                : $placeholder;
            $record['stock'] = (int) StockAvailable::getQuantityAvailableByProduct((int) $record['id_product']);
            $records[] = $record;
        }

        $params['data'] = new GridData(
            new RecordCollection($records),
            $data->getRecordsTotal(),
            $data->getQuery()
        );
    }

    public function hookDisplayBackOfficeHeader()
    {
        $tables = array_map(fn ($id) => '#' . $id . '_grid_table', array_values(self::GRIDS));
        $rule = function (string $suffixes, string $css) use ($tables): string {
            $selectors = [];
            foreach ($tables as $table) {
                foreach (explode(',', $suffixes) as $suffix) {
                    $selectors[] = $table . ' ' . trim($suffix);
                }
            }

            return implode(',', $selectors) . '{' . $css . '}';
        };

        // Equal-width columns (bulk checkbox stays narrow; the 7 remaining columns split the rest), photo shown in a 120px square (whole picture visible),
        // status toggle aligned left like the other cells.
        return '<style>'
            . implode(',', $tables) . '{table-layout:fixed;width:100%}'
            . $rule('th:first-child, td:first-child', 'width:56px')
            . $rule('th:not(:first-child)', 'width:calc((100% - 56px) / 7)')
            . $rule('th, td', 'text-align:left;overflow:hidden;text-overflow:ellipsis')
            . $rule('th:last-child, td:last-child', 'text-align:right')
            . $rule('th[data-column-id="stock"], td.column-stock', 'text-align:center')
            . $rule('th', 'font-size:1rem;padding-top:1rem;padding-bottom:1rem')
            . $rule('td', 'vertical-align:middle')
            . $rule('td.column-image img', 'width:120px;height:120px;max-width:none;max-height:none;object-fit:contain;border-radius:8px')
            . $rule('td .text-center', 'text-align:left!important')
            . $rule('td .ps-switch-center', 'margin-left:0!important;margin-right:auto!important')
            . '</style>';
    }
}
