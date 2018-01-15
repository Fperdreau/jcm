<?php

namespace includes;

class TypesManager
{
    private static $instances = array();

    public function __construct()
    {
    }

    private static function factory($controller)
    {
        if (!isset(self::$instances[$controller])) {
            $controllerName = '\\includes\\' . ($controller);
            try {
                self::$instances[$controller] = new $controllerName();
            } catch (\Exception $e) {
                Logger::getInstance(APP_NAME)->error("Could not instantiate '{$controllerName}'");
            }
        }
        return self::$instances[$controller];
    }

    /**
     * Get session types
     *
     * @return array
     */
    public static function getTypes($className)
    {
        if (!empty(self::factory($className)->getSettings('types'))) {
            $types = self::factory($className)->getSettings('types');
        } else {
            $types = self::factory($className)->getSettings('defaults');
        }
        return array(
            'types'=>$types,
            'defaults'=>self::factory($className)->getSettings('defaults'),
            'default'=>self::factory($className)->getSettings('default_type')
        );
    }

    /**
     * Add session type
     *
     * @param string $type: new session type
     * @return array: returns updated list of types
     */
    public function addType($className, $type)
    {
        $obj = self::factory($className);

        $typesOld = self::getTypes($className);
        if (!in_array($type, $typesOld['types'])) {
            $types = array_merge($typesOld['types'], array($type));
            $result = $obj->updateSettings(array('types'=>$types));
            if ($result['status']) {
                //Get updated list of types
                $result['msg'] = self::renderTypes(
                    $className,
                    $types,
                    $typesOld['defaults']
                )['types'];
            } else {
                $result['status'] = false;
                $result['msg'] = 'Oops, something went wrong';
            }
        } else {
            $result['status'] = false;
            $result['msg'] = 'This type already exists';
        }
        return $result;
    }

    /**
     * Delete session type
     *
     * @param string $type: session type to delete
     * @return array|bool: returns updated list of types if successful, false otherwise
     */
    public function deleteType($className, $type)
    {
        $obj = self::factory($className);

        // List of session types
        $types = self::getTypes($className);

        if (in_array($type, $types['defaults'])) {
            $result['status'] = false;
            $result['msg'] = "Defaults types cannot be deleted";
        } else {
            if (($key = array_search($type, $types['types'])) !== false) {
                unset($types[$key]);
            }
            $new_types = array_values(array_diff($types['types'], array($type)));
            $updated = $obj->updateSettings(array("types"=>$new_types));
            if ($updated['status']) {
                //Get session types
                $result = self::renderTypes(
                    $className,
                    $new_types,
                    $types['defaults']
                )['types'];
            }
        }
        return $result;
    }

    /**
     * Render form
     *
     * @param string $className: controller name
     * @param array $types: array('options'=>select input, 'types'=>types list)
     * @return string
     */
    public static function form($className, array $types)
    {
        $url = Router::buildUrl(
            'Session',
            'updateSettings'
        );
        $lowerName = strtolower($className);
        return "
        <div id='session_types_options'>
            <div id='renderTypes' style='position: relative; margin-bottom: 20px;'>
                <div class='form-group'>
                    <select name='default_type' class='actionOnSelect' data-url='{$url}' 
                        id='{$lowerName}'>
                        {$types['options']}
                    </select>
                    <label>Default type</label>
                </div>
            </div>
            <div style='font-size: 0;'>
                <button class='type_add addBtn' data-class='{$className}' value='+'/>
                <input id='new_{$lowerName}_type' type='text' placeholder='New Category'/>
            </div>
            <div class='type_list' id='{$lowerName}'>{$types['types']}</div>
        </div>
        ";
    }

    /**
     * Get session types
     *
     * @param array $types: List of presentation types
     * @param $default_type : defaults session type
     * @param array $exclude : list of excluded types
     * @return array: array('types'=>list of types, 'options'=>select input with types list)
     */
    public static function renderTypes($className, array $types, $default_type = null, array $exclude = array())
    {
        $Sessionstype = "";
        $opttypedflt = "";
        foreach ($types as $type) {
            if (in_array($type, $exclude)) {
                continue;
            }
            $Sessionstype .= self::typeDiv($type, $className);
            $opttypedflt .= $type == $default_type ?
                "<option value='$type' selected>$type</option>"
                : "<option value='$type'>$type</option>";
        }
        return array(
            'types'=>$Sessionstype,
            "options"=>$opttypedflt
        );
    }

    /**
     * Render session/presentation type list
     * @param $data
     * @return string
     */
    public static function typeDiv($data, $className)
    {
        $div_id = strtolower($className) .'_'. str_replace(' ', '_', strtolower($data));
        return "
                <div class='type_div' id='{$div_id}'>
                    <div class='type_name'>".ucfirst($data)."</div>
                    <div class='type_del' data-type='$data' data-class='" . strtolower($className). "'></div>
                </div>
            ";
    }

    /**
     * Undocumented function
     *
     * @param string $className
     * @param null|string $selected
     * @return array: array('types'=>list of types, 'options'=>select input with types list)
     */
    public static function getTypeSelectInput($className, $selected = null)
    {
        $data = self::getTypes($className);
        return self::renderTypes($className, $data['types'], is_null($selected) ? $data['default'] : $selected);
    }
}
