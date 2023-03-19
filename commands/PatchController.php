<?php

namespace app\commands;

use Yii;
use console\patch\Patch;
use yii\console\Controller;

/**
 * Class PatchController
 */
class PatchController extends Controller
{
    /**
     * @var string the default command action.
     */
    public $defaultAction = 'apply';

    /**
     * @var string
     */
    public $patchPath = '@app/patches';

    /**
     * Display list of available patches
     */
    protected function listPatches()
    {
        $patchPath = Yii::getAlias($this->patchPath);
        $patches = [];
        $handle = opendir($patchPath);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $patchPath . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^(p(\d{6}_?\d{6})\D.*?)\.php$/is', $file, $matches) && is_file($path)) {
                $class = $matches[1];
                $patches[] = $class;
            }
        }
        closedir($handle);
        sort($patches);
        $this->stdout("Available patches:\n");
        foreach ($patches as $patch) {
            $this->stdout("\t$patch\n");
        }
        $this->stdout("\n");
    }

    /**
     * Apply patch
     *
     * @param string $name Name of the patch
     */
    public function actionApply($name = null)
    {
        if (empty($name)) {
            $this->listPatches();
            return;
        }
        $patchPath = Yii::getAlias($this->patchPath);
        $file = $patchPath . DIRECTORY_SEPARATOR . $name . '.php';
        require_once $file;
        /** @var Patch $patch */
        $patch = new $name;
        $patch->run();
    }
}
