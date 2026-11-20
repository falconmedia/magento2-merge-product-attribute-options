<?php

namespace FalconMedia\MergeAttributeOptions\Console;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Store\Api\StoreRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractRenameOption extends Command
{
    const ATTRIBUTE_CODE = 'attribute_code';
    const OPTION_ID = 'option_id';
    const LABEL = 'label';

    /** @var State */
    protected $state;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var ResourceConnection */
    protected $resource;

    /** @var StoreRepositoryInterface */
    protected $storeRepository;

    /** @var TypeListInterface */
    protected $cacheTypeList;

    public function __construct(
        State $state,
        AttributeRepositoryInterface $attributeRepository,
        ResourceConnection $resource,
        StoreRepositoryInterface $storeRepository,
        TypeListInterface $cacheTypeList
    ) {
        $this->state = $state;
        $this->attributeRepository = $attributeRepository;
        $this->resource = $resource;
        $this->storeRepository = $storeRepository;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct();
    }

    /**
     * Store ID the label is written to for this command.
     */
    abstract protected function getStoreId(InputInterface $input): int;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Area code already set.
        }

        try {
            $attributeCode = (string) $input->getArgument(self::ATTRIBUTE_CODE);
            $optionId = (int) $input->getArgument(self::OPTION_ID);
            $label = trim((string) $input->getArgument(self::LABEL));
            $storeId = $this->getStoreId($input);

            if ($label === '') {
                throw new \InvalidArgumentException('Label cannot be empty.');
            }
            if ($storeId !== 0) {
                $this->storeRepository->getById($storeId);
            }

            $attribute = $this->attributeRepository->get('catalog_product', $attributeCode);

            $connection = $this->resource->getConnection();
            $optionTable = $this->resource->getTableName('eav_attribute_option');
            $valueTable = $this->resource->getTableName('eav_attribute_option_value');

            $belongs = $connection->fetchOne(
                $connection->select()
                    ->from($optionTable, 'option_id')
                    ->where('option_id = ?', $optionId)
                    ->where('attribute_id = ?', (int) $attribute->getAttributeId())
            );
            if (!$belongs) {
                throw new \InvalidArgumentException(
                    sprintf("Option %d does not belong to attribute '%s'.", $optionId, $attributeCode)
                );
            }

            $old = $connection->fetchOne(
                $connection->select()
                    ->from($valueTable, 'value')
                    ->where('option_id = ?', $optionId)
                    ->where('store_id = ?', $storeId)
            );

            if ($old === false) {
                $connection->insert($valueTable, [
                    'option_id' => $optionId,
                    'store_id' => $storeId,
                    'value' => $label,
                ]);
            } else {
                $connection->update(
                    $valueTable,
                    ['value' => $label],
                    ['option_id = ?' => $optionId, 'store_id = ?' => $storeId]
                );
            }

            $this->cacheTypeList->cleanType('eav');

            $output->writeln(sprintf(
                "<info>Option %d of '%s' (store %d): '%s' => '%s'</info>",
                $optionId,
                $attributeCode,
                $storeId,
                $old === false ? '' : $old,
                $label
            ));

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
