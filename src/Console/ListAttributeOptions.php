<?php

namespace FalconMedia\MergeAttributeOptions\Console;

use Magento\Framework\App\State;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class ListAttributeOptions extends Command
{
    const ATTRIBUTE_CODE = 'attribute_code';

    private $state;
    private $attributeRepository;
    private $optionFactory;
    private $resource;

    public function __construct(
        State $state,
        AttributeRepositoryInterface $attributeRepository,
        OptionFactory $optionFactory,
        ResourceConnection $resource
    ) {
        $this->state = $state;
        $this->attributeRepository = $attributeRepository;
        $this->optionFactory = $optionFactory;
        $this->resource = $resource;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('falconmedia:attribute:list-options')
            ->setDescription('List attribute options with product count')
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

            $output->writeln("<info>Fetching attribute options for '{$attributeCode}'</info>");

            /** @var AdapterInterface $connection */
            $connection = $this->resource->getConnection();
            $attributeId = $attribute->getAttributeId();
            $optionTable = $this->resource->getTableName('eav_attribute_option');
            $valueTable = $this->resource->getTableName('eav_attribute_option_value');
            $productTable = $this->resource->getTableName('catalog_product_entity_int');

            // Fetch all options for this attribute
            $query = $connection->select()
                ->from(['o' => $optionTable], ['option_id'])
                ->joinLeft(['v' => $valueTable], 'o.option_id = v.option_id AND v.store_id = 0', ['value'])
                ->where('o.attribute_id = ?', $attributeId)
                ->order('o.option_id ASC');

            $options = $connection->fetchAll($query);

            if (empty($options)) {
                $output->writeln('<comment>No options found.</comment>');
                return Cli::RETURN_SUCCESS;
            }

            // Prepare table
            $table = new Table($output);
            $table->setHeaders(['Option ID', 'Label', 'Product Count']);

            foreach ($options as $option) {
                $optionId = $option['option_id'];
                $label = $option['value'] ?: '[No Label]';

                // Count how many products use this option
                $countQuery = $connection->select()
                    ->from($productTable, ['count' => new \Zend_Db_Expr('COUNT(*)')])
                    ->where('attribute_id = ?', $attributeId)
                    ->where('value = ?', $optionId);

                $productCount = (int) $connection->fetchOne($countQuery);

                $table->addRow([$optionId, $label, $productCount]);
            }

            $table->render();

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
