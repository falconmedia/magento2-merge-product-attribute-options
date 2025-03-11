<?php

namespace FalconMedia\MergeAttributeOptions\Console;

use Magento\Framework\App\State;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\StoreRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ListAttributeOptionsStore extends Command
{
    const ATTRIBUTE_CODE = 'attribute_code';

    private $state;
    private $attributeRepository;
    private $optionFactory;
    private $storeRepository;
    private $resource;

    public function __construct(
        State $state,
        AttributeRepositoryInterface $attributeRepository,
        OptionFactory $optionFactory,
        StoreRepositoryInterface $storeRepository,
        ResourceConnection $resource
    ) {
        $this->state = $state;
        $this->attributeRepository = $attributeRepository;
        $this->optionFactory = $optionFactory;
        $this->storeRepository = $storeRepository;
        $this->resource = $resource;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('falconmedia:attribute:list-options-store')
            ->setDescription('List attribute option IDs and labels per store')
            ->addArgument(self::ATTRIBUTE_CODE, InputArgument::REQUIRED, 'Attribute Code');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);

            $attributeCode = $input->getArgument(self::ATTRIBUTE_CODE);
            $attribute = $this->attributeRepository->get('catalog_product', $attributeCode);

            if (!$attribute->getId()) {
                throw new \Exception(__('Attribute not found.'));
            }

            $output->writeln("<info>Attribute: {$attributeCode}</info>");

            /** @var AdapterInterface $connection */
            $connection = $this->resource->getConnection();
            $optionTable = $this->resource->getTableName('eav_attribute_option');
            $valueTable = $this->resource->getTableName('eav_attribute_option_value');

            // Get all stores
            $stores = $this->storeRepository->getList();
            $storeLabels = [];

            foreach ($stores as $store) {
                $storeLabels[$store->getId()] = $store->getName();
            }

            // Fetch all attribute options for the given attribute
            $query = $connection->select()
                ->from(['o' => $optionTable], ['option_id'])
                ->joinLeft(['v' => $valueTable], 'o.option_id = v.option_id', ['store_id', 'value'])
                ->where('o.attribute_id = ?', $attribute->getAttributeId())
                ->order('o.option_id ASC');

            $results = $connection->fetchAll($query);

            $optionsData = [];

            foreach ($results as $row) {
                $optionId = $row['option_id'];
                $storeId = $row['store_id'] ?? 0;
                $label = $row['value'] ?? '';

                if (!isset($optionsData[$optionId])) {
                    $optionsData[$optionId] = [];
                }

                $storeName = $storeLabels[$storeId] ?? 'Default';
                $optionsData[$optionId][] = [$optionId, $storeId, $storeName, $label];
            }

            // Display in table format
            $table = new Table($output);
            $table->setHeaders(['Option ID', 'Store ID', 'Store Name', 'Label']);

            foreach ($optionsData as $rows) {
                foreach ($rows as $row) {
                    $table->addRow($row);
                }
            }

            $table->render();

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
