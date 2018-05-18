<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Platforms;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Firebear\ImportExport\Model\Import\Platforms
     */
    protected $platforms;

    /**
     * Options constructor.
     *
     * @param \Firebear\ImportExport\Model\Import\Platforms $platforms
     */
    public function __construct(
        \Firebear\ImportExport\Model\Import\Platforms $platforms
    ) {
        $this->platforms = $platforms;
    }
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => __('Select'), 'value' => ''];
        $list = $this->platforms->toOptionArrayNames();
  
        foreach ($list as $data) {
            $options[] = ['label' => $data['label'], 'value' => $data['value']];
        }
        
        $this->options = $options;

        return $this->options;
    }
}
