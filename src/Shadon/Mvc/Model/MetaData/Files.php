<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shadon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData\Files as MetaDataFiles;

/**
 * Class Files.
 *
 * @author hehui<hehui@eelly.net>
 */
class Files extends MetaDataFiles
{
    /**
     * reset.
     */
    public function reset(): void
    {
        $meta = $this->_metaData;
        if (\is_array($meta)) {
            foreach ($meta as $key => $value) {
                $path = $this->_metaDataDir.'meta-'.str_replace('\\', '_', $key).'.php';
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
        parent::reset();
    }
}
