<?php
namespace DreamFactory\Core\Database\Seeds;

use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\Models\BaseModel;
use Illuminate\Database\Seeder;

class BaseModelSeeder extends Seeder
{
    protected $modelClass = null;

    protected $recordIdentifier = 'name';

    protected $allowUpdate = false;

    //  Add the default system_resources
    protected $records = [];

    /**
     * Run the database seeds.
     *
     * @throws \Exception
     */
    public function run()
    {
        BaseModel::unguard();

        if (empty($this->modelClass)) {
            throw new \Exception("Invalid seeder model. No value for {$this->modelClass}.");
        }

        /** @var BaseModel $modelName */
        $modelName = $this->modelClass;
        $created = [];
        $updated = [];
        $extras = $this->getRecordExtras();
        foreach ($this->records as $record) {
            $record = array_merge($record, $extras);

            /** @type \Illuminate\Database\Eloquent\Builder $builder */
            $builder = null;
            $name = '';
            if (!is_array($this->recordIdentifier)) {
                $name = ArrayUtils::get($record, $this->recordIdentifier);
                if (empty($name)) {
                    throw new \Exception("Invalid seeder record. No value for {$this->recordIdentifier}.");
                }
                $builder = $modelName::where($this->recordIdentifier, $name);
            } else {
                foreach ($this->recordIdentifier as $identifier) {
                    $id = ArrayUtils::get($record, $identifier);
                    if (empty($id)) {
                        throw new \Exception("Invalid seeder record. No value for $identifier.");
                    }
                    $builder =
                        (!$builder) ? $modelName::where($identifier, $id) : $builder->where($identifier, $id);
                    $name .= (empty($name)) ? $id : ':' . $id;
                }
            }
            if (!$builder->exists()) {
                // seed the record
                $modelName::create($record);
                $created[] = $name;
            } elseif ($this->allowUpdate) {
                // update an existing record
                $updated[] = $name;
            }
        }

        $this->outputMessage($created, $updated);
    }

    protected function outputMessage(array $created = [], array $updated = [])
    {
        $msg = static::separateWords(static::getModelBaseName($this->modelClass)) . ' resources';

        if (!empty($created)) {
            $this->command->info($msg . ' created: ' . implode(', ', $created));
        }
        if (!empty($updated)) {
            $this->command->info($msg . ' updated: ' . implode(', ', $updated));
        }
    }

    protected function getRecordExtras()
    {
        return [];
    }

    public static function getModelBaseName($fqcn)
    {
        if (preg_match('@\\\\([\w]+)$@', $fqcn, $matches)) {
            $fqcn = $matches[1];
        }

        return $fqcn;
    }

    public static function separateWords($string)
    {
        return preg_replace("/([a-z])([A-Z])/", "\\1 \\2", $string);
    }
}
