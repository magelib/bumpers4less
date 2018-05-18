<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\History\Type;

use Magento\Framework\Data\OptionSourceInterface;
use Firebear\ImportExport\Model\Source\Config;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => 'Admin', 'value' => 'admin'];
        $options[] = ['label' => 'Cron', 'value' => 'cron'];
        $options[] = ['label' => 'Command', 'command'];

        $this->options = $options;

        return $this->options;
    }

}
