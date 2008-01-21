<?php
    /**
     * @class  moduleModel
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 Model class
     **/

    class moduleModel extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief document_srl로 모듈의 정보르 구함
         * 이 경우는 캐시파일을 이용할 수가 없음
         **/
        function getModuleInfoByDocumentSrl($document_srl) {
            $args->document_srl = $document_srl;
            $output = executeQuery('module.getModuleInfoByDocument', $args);

            return $this->arrangeModuleInfo($output->data);
        }

        /**
         * @brief mid로 모듈의 정보를 구함
         **/
        function getModuleInfoByMid($mid='') {
            // $mid값이 인자로 주어질 경우 $mid로 모듈의 정보를 구함
            if($mid) {
                $args->mid = $mid;
                $output = executeQuery('module.getMidInfo', $args);
            }

            // 모듈의 정보가 없다면($mid가 잘못이거나 없었을 경우) 기본 모듈을 가져옴
            if(!$output->data) $output = executeQuery('module.getDefaultMidInfo');

            return $this->arrangeModuleInfo($output->data);
        }

        /**
         * @brief module_srl에 해당하는 모듈의 정보를 구함
         **/
        function getModuleInfoByModuleSrl($module_srl) {
            // 데이터를 가져옴
            $args->module_srl = $module_srl;
            $output = executeQuery('module.getMidInfo', $args);
            if(!$output->data) return;

            return $this->arrangeModuleInfo($output->data);
        }

        /**
         * @brief layout_srl에 해당하는 모듈의 정보를 구함
         **/
        function getModulesInfoByLayout($layout_srl) {
            // 데이터를 가져옴
            $args->layout_srl = $layout_srl;
            $output = executeQueryArray('module.getModulesByLayout', $args);

            $count = count($output->data);

            $modules = array();
            for($i=0;$i<$count;$i++) {
                $modules[] = $this->arrangeModuleInfo($output->data[$i]);
            }
            return $modules;
        }

        /**
         * @brief 여러개의 module_srl에 해당하는 모듈의 정보를 구함
         **/
        function getModulesInfo($module_srls) {
            $args->module_srls = $module_srls;
            $output = executeQueryArray('module.getModulesInfo', $args);
            if(!$output->toBool()) return;
            return $output->data;
        }

        /**
         * @brief DB에 생성된 mid 전체 목록을 구해옴
         **/
        function getMidList($args = null) {
            $output = executeQuery('module.getMidList', $args);
            if(!$output->toBool()) return $output;

            $list = $output->data;
            if(!$list) return;

            if(!is_array($list)) $list = array($list);

            foreach($list as $val) {
                $mid_list[$val->mid] = $val;
            }
            return $mid_list;
        }

        /**
         * @brief mid 목록에 대응하는 module_srl을 배열로 return
         **/
        function getModuleSrlByMid($mid) {
            if(is_array($mid)) $mid = "'".implode("','",$mid)."'";

            $args->mid = $mid;
            $output = executeQuery('module.getModuleSrlByMid', $args);
            if(!$output->toBool()) return $output;

            $list = $output->data;
            if(!$list) return;
            if(!is_array($list)) $list = array($list);

            foreach($list as $key => $val) {
                $module_srl_list[] = $val->module_srl;
            }

            return $module_srl_list;
        }

        /**
         * @brief DB에서 가져온 원 모듈 정보에서 grant, extraVar등의 정리
         **/
        function arrangeModuleInfo($source_module_info) {
            if(!$source_module_info || !is_object($source_module_info) ) return;

            // serialize되어 있는 변수들 추출
            $extra_vars = $source_module_info->extra_vars;
            $skin_vars = $source_module_info->skin_vars;
            $grants = $source_module_info->grants;
            $admin_id = $source_module_info->admin_id;

            unset($source_module_info->extra_vars);
            unset($source_module_info->skin_vars);
            unset($source_module_info->grants);
            unset($source_module_info->admin_id);

            $module_info = clone($source_module_info);

            // extra_vars의 정리
            if($extra_vars) {
                $extra_vars = unserialize($extra_vars);
                foreach($extra_vars as $key => $val) if(!$module_info->{$key}) $module_info->{$key} = $val;
            }

            // skin_vars의 정리
            if($skin_vars) {
                $skin_vars = unserialize($skin_vars);
                foreach($skin_vars as $key => $val) if(!$module_info->{$key}) $module_info->{$key} = $val;
            }

            // 권한의 정리
            if($grants) $module_info->grants = unserialize($grants);

            // 관리자 아이디의 정리
            if($admin_id) $module_info->admin_id = explode(',',$admin_id);
            else $module_info->admin_id = array();

            return $module_info;
        }

        /**
         * @brief act 값에 의한 forward 값을 구함
         **/
        function getActionForward($act) {
            $args->act = $act;
            $output = executeQuery('module.getActionForward',$args);
            return $output->data;
        }

        /**
         * @brief trigger_name에 등록된 모든 목록을 추출
         **/
        function getTriggers($trigger_name, $called_position) {
            $args->trigger_name = $trigger_name;
            $args->called_position = $called_position;
            $output = executeQueryArray('module.getTriggers',$args);
            return $output->data;
        }

        /**
         * @brief 특정 trigger_name의 특정 대상을 추출
         **/
        function getTrigger($trigger_name, $module, $type, $called_method, $called_position) {
            $args->trigger_name = $trigger_name;
            $args->module = $module;
            $args->type = $type;
            $args->called_method = $called_method;
            $args->called_position = $called_position;
            $output = executeQuery('module.getTrigger',$args);
            return $output->data;
        }

        /**
         * @brief 주어진 곳의 스킨 목록을 구함 
         * 스킨과 skin.xml 파일을 분석 정리한 결과를 return
         **/
        function getSkins($path) {
            $skin_path = sprintf("%s/skins/", $path);
            $list = FileHandler::readDir($skin_path);
            if(!count($list)) return;

            foreach($list as $skin_name) {
                unset($skin_info);
                $skin_info = $this->loadSkinInfo($path, $skin_name);
                if(!$skin_info) $skin_info->title = $skin_name;

                $skin_list[$skin_name] = $skin_info;
            }

            return $skin_list;
        }


        /**
         * @brief 특정 위치의 특정 스킨의 정보를 구해옴
         **/
        function loadSkinInfo($path, $skin) {

            // 모듈의 스킨의 정보 xml 파일을 읽음
            $skin_xml_file = sprintf("%sskins/%s/skin.xml", $path, $skin);
            if(!file_exists($skin_xml_file)) return;

            // XmlParser 객체 생성
            $oXmlParser = new XmlParser();
            $_xml_obj = $oXmlParser->loadXmlFile($skin_xml_file);

            // 스킨 정보가 없으면 return
            if(!$_xml_obj->skin) return;
            $xml_obj = $_xml_obj->skin;

            // 스킨이름
            $skin_info->title = $xml_obj->title->body;

            // 작성자 정보
            $skin_info->maker->name = $xml_obj->maker->name->body;
            $skin_info->maker->email_address = $xml_obj->maker->attrs->email_address;
            $skin_info->maker->homepage = $xml_obj->maker->attrs->link;
            $skin_info->maker->date = $xml_obj->maker->attrs->date;
            $skin_info->maker->description = $xml_obj->maker->description->body;

            // colorset
            $colorset = $xml_obj->colorset->color;
            if($colorset) {
                if(!is_array($colorset)) $colorset = array($colorset);

                foreach($colorset as $color) {
                    $name = $color->attrs->name;
                    $title = $color->title->body;
                    $screenshot = $color->attrs->src;
                    if($screenshot) {
                        $screenshot = sprintf("%sskins/%s/%s", $path, $skin, $screenshot);
                        if(!file_exists($screenshot)) $screenshot = "";
                    } else $screenshot = "";

                    unset($obj);
                    $obj->name = $name;
                    $obj->title = $title;
                    $obj->screenshot = $screenshot;
                    $skin_info->colorset[] = $obj;
                }
            }

            // 스킨에서 사용되는 변수들
            $extra_vars = $xml_obj->extra_vars->var;
            if($extra_vars) {
                
                if(!is_array($extra_vars)) $extra_vars = array($extra_vars);

                foreach($extra_vars as $var) {
                    unset($obj);

                    $name = $var->attrs->name;
                    $type = $var->attrs->type;
                    $title = $var->title->body;
                    $description = $var->description->body;

                    if($var->default) {
                        unset($default);
                        if(is_array($var->default)) {
                            for($i=0;$i<count($var->default);$i++) $default[] = $var->default[$i]->body;
                        } else {
                            $default = $var->default->body;
                        }
                    }

                    $width = $var->attrs->width;
                    $height = $var->attrs->height;

                    unset($obj);
                    $obj->title = $title;
                    $obj->description = $description;
                    $obj->name = $name;
                    $obj->type = $type;
                    $obj->default = $default;
                    $obj->width = $width;
                    $obj->height = $height;

                    $skin_info->extra_vars[] = $obj;
                }
            }

            // 메뉴 종류 (레이아웃을 위한 설정)
            if($xml_obj->menus->menu) {
                $menus = $xml_obj->menus->menu;
                if(!is_array($menus)) $menus = array($menus);

                $menu_count = count($menus);
                $skin_info->menu_count = $menu_count;
                for($i=0;$i<$menu_count;$i++) {
                    unset($obj);

                    $obj->name = $menus[$i]->attrs->name;
                    if($menus[$i]->attrs->default == "true") $obj->default = true;
                    $obj->title = $menus[$i]->title->body;
                    $obj->maxdepth = $menus[$i]->maxdepth->body;

                    $skin_info->menu->{$obj->name} = $obj;
                }
            }

            return $skin_info;
        }

        /**
         * @brief module의 conf/module.xml 을 통해 grant(권한) 및 action 데이터를 return
         * module.xml 파일의 경우 파싱하는데 시간이 걸리기에 캐싱을 한다...
         * 캐싱을 할때 바로 include 할 수 있도록 역시 코드까지 추가하여 캐싱을 한다.
         * 이게 퍼포먼스 상으로는 좋은데 어떤 부정적인 결과를 유도할지는 잘 모르겠...
         **/
        function getModuleActionXml($module) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $class_path = ModuleHandler::getModulePath($module);
            if(!$class_path) return;

            // 해당 경로에 module.xml 파일이 있는지 체크한다. 없으면 return
            $xml_file = sprintf("%sconf/module.xml", $class_path);
            if(!file_exists($xml_file)) return;

            // 캐시된 파일이 있는지 확인
            $cache_file = sprintf("./files/cache/module_info/%s.%s.php", $module, Context::getLangType());

            // 캐시 파일이 없거나 캐시 파일이 xml 파일보다 오래되었으면 내용 다시 갱신
            if(!file_exists($cache_file) || filemtime($cache_file)<filemtime($xml_file)) {

                $buff = ""; ///< 캐시 파일에 쓸 buff 변수 설정

                $xml_obj = XmlParser::loadXmlFile($xml_file); ///< xml 파일을 읽어서 xml object로 변환

                if(!count($xml_obj->module)) return; ///< xml 내용중에 module 태그가 없다면 오류;;

                $grants = $xml_obj->module->grants->grant; ///< 권한 정보 (없는 경우도 있음)
                $permissions = $xml_obj->module->permissions->permission; ///< 권한 대행 (없는 경우도 있음)
                $actions = $xml_obj->module->actions->action; ///< action list (필수)

                $default_index = $admin_index = '';

                // 권한 정보의 정리
                if($grants) {
                    if(is_array($grants)) $grant_list = $grants;
                    else $grant_list[] = $grants;

                    foreach($grant_list as $grant) {
                        $name = $grant->attrs->name;
                        $default = $grant->attrs->default?$grant->attrs->default:'guest';
                        $title = $grant->title->body;

                        $info->grant->{$name}->title = $title;
                        $info->grant->{$name}->default = $default;

                        $buff .= sprintf('$info->grant->%s->title=\'%s\';', $name, $title);
                        $buff .= sprintf('$info->grant->%s->default=\'%s\';', $name, $default);
                    }
                }

                // 권한 허용 정리
                if($permissions) {
                    if(is_array($permissions)) $permission_list = $permissions;
                    else $permission_list[] = $permissions;

                    foreach($permission_list as $permission) {
                        $action = $permission->attrs->action;
                        $target = $permission->attrs->target;

                        $info->permission->{$action} = $target;

                        $buff .= sprintf('$info->permission->%s = \'%s\';', $action, $target);
                    }
                }

                // actions 정리
                if($actions) {
                    if(is_array($actions)) $action_list = $actions;
                    else $action_list[] = $actions;

                    foreach($action_list as $action) {
                        $name = $action->attrs->name;

                        $type = $action->attrs->type;
                        $grant = $action->attrs->grant?$action->attrs->grant:'guest';
                        $standalone = $action->attrs->standalone=='true'?'true':'false';

                        $index = $action->attrs->index;
                        $admin_index = $action->attrs->admin_index;

                        $output->action->{$name}->type = $type;
                        $output->action->{$name}->grant = $grant;
                        $output->action->{$name}->standalone= $standalone;

                        $info->action->{$name}->type = $type;
                        $info->action->{$name}->grant = $grant;
                        $info->action->{$name}->standalone = $standalone=='true'?true:false;

                        $buff .= sprintf('$info->action->%s->type=\'%s\';', $name, $type);
                        $buff .= sprintf('$info->action->%s->grant=\'%s\';', $name, $grant);
                        $buff .= sprintf('$info->action->%s->standalone=%s;', $name, $standalone);

                        if($index=='true') {
                            $default_index_act = $name;
                            $info->default_index_act = $name;
                        }
                        if($admin_index=='true') {
                            $admin_index_act = $name;
                            $info->admin_index_act = $name;
                        }
                    }
                }
                $buff = sprintf('<?php if(!defined("__ZBXE__")) exit();$info->default_index_act = \'%s\';$info->admin_index_act = \'%s\';%s?>', $default_index_act, $admin_index_act, $buff);

                FileHandler::writeFile($cache_file, $buff);

                return $info;
            }

            @include($cache_file); 

            return $info;
        }

        /**
         * @brief 특정 모듈의 설정 정보 return
         * 캐시된 설정 정보가 없으면 만들 후 캐시하고 return
         **/
        function getModuleConfig($module) {
            if(!$GLOBALS['__ModuleConfig__'][$module]) {
                $cache_file = sprintf('./files/cache/module_info/%s.config.php',$module);

                if(!file_exists($cache_file)) {
                    $args->module = $module;
                    $output = executeQuery('module.getModuleConfig', $args);

                    $config = base64_encode($output->data->config);

                    $buff = sprintf('<?php if(!defined("__ZBXE__")) exit(); $config = "%s"; ?>', $config);

                    FileHandler::writeFile($cache_file, $buff);
                }

                if(!$config && file_exists($cache_file)) @include($cache_file);

                $GLOBALS['__ModuleConfig__'][$module] = unserialize(base64_decode($config));
            }
            return $GLOBALS['__ModuleConfig__'][$module];
        }


        /**
         * @brief 모듈의 conf/info.xml 을 읽어서 정보를 구함
         **/
        function getModuleInfoXml($module) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $module_path = ModuleHandler::getModulePath($module);
            if(!$module_path) return;

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $xml_file = sprintf("%s/conf/info.xml", $module_path);
            if(!file_exists($xml_file)) return;

            $oXmlParser = new XmlParser();
            $tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
            $xml_obj = $tmp_xml_obj->module;

            if(!$xml_obj) return;

            $info->title = $xml_obj->title->body;

            // 작성자 정보
            $module_info->title = $xml_obj->title->body;
            $module_info->version = $xml_obj->attrs->version;
            $module_info->category = $xml_obj->attrs->category;
            if(!$module_info->category) $module_info->category = 'service';
            $module_info->author->name = $xml_obj->author->name->body;
            $module_info->author->email_address = $xml_obj->author->attrs->email_address;
            $module_info->author->homepage = $xml_obj->author->attrs->link;
            $module_info->author->date = $xml_obj->author->attrs->date;
            $module_info->author->description = $xml_obj->author->description->body;

            // history 
            if(!is_array($xml_obj->history->author)) $history[] = $xml_obj->history->author;
            else $history = $xml_obj->history->author;

            foreach($history as $item) {
                unset($obj);
                $obj->name = $item->name->body;
                $obj->email_address = $item->attrs->email_address;
                $obj->homepage = $item->attrs->link;
                $obj->date = $item->attrs->date;
                $obj->description = $item->description->body;
                $module_info->history[] = $obj;
            }

            // action 정보를 얻어서 admin_index를 추가
            $action_info = $this->getModuleActionXml($module);
            $module_info->admin_index_act = $action_info->admin_index_act;

            return $module_info;
        }

        /**
         * @brief 모듈 카테고리의 목록을 구함
         **/
        function getModuleCategories() {
            // 데이터를 DB에서 가져옴
            $output = executeQuery('module.getModuleCategories');
            if(!$output->toBool()) return $output;
            $list = $output->data;
            if(!$list) return;
            if(!is_array($list)) $list = array($list);

            foreach($list as $val) {
                $category_list[$val->module_category_srl] = $val;
            }
            return $category_list;
        }

        /**
         * @brief 특정 모듈 카테고리의 내용을 구함
         **/
        function getModuleCategory($module_category_srl) {
            // 데이터를 DB에서 가져옴
            $args->module_category_srl = $module_category_srl;
            $output = executeQuery('module.getModuleCategory', $args);
            if(!$output->toBool()) return $output;
            return $output->data;
        }

        /**
         * @brief 모듈의 xml 정보만 구함
         **/
        function getModulesXmlInfo() {
            // 다운받은 모듈과 설치된 모듈의 목록을 구함
            $searched_list = FileHandler::readDir('./modules');
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            for($i=0;$i<$searched_count;$i++) {
                // 모듈의 이름
                $module_name = $searched_list[$i];

                $path = ModuleHandler::getModulePath($module_name);

                // 해당 모듈의 정보를 구함
                $info = $this->getModuleInfoXml($module_name);
                unset($obj);

                $info->module = $module_name;
                $info->created_table_count = $created_table_count;
                $info->table_count = $table_count;
                $info->path = $path;
                $info->admin_index_act = $info->admin_index_act;
                $list[] = $info;
            }
            return $list;
        }

        /**
         * @brief 모듈의 종류와 정보를 구함
         **/
        function getModuleList() {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 다운받은 모듈과 설치된 모듈의 목록을 구함
            $searched_list = FileHandler::readDir('./modules');
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            for($i=0;$i<$searched_count;$i++) {
                // 모듈의 이름
                $module_name = $searched_list[$i];

                $path = ModuleHandler::getModulePath($module_name);

                // schemas내의 테이블 생성 xml파일수를 구함
                $tmp_files = FileHandler::readDir($path."schemas", '/(\.xml)$/');
                $table_count = count($tmp_files);

                // 테이블이 설치되어 있는지 체크
                $created_table_count = 0;
                for($j=0;$j<count($tmp_files);$j++) {
                    list($table_name) = explode(".",$tmp_files[$j]);
                    if($oDB->isTableExists($table_name)) $created_table_count ++;
                }

                // 해당 모듈의 정보를 구함
                $info = $this->getModuleInfoXml($module_name);
                unset($obj);

                $info->module = $module_name;
                $info->category = $info->category;
                $info->created_table_count = $created_table_count;
                $info->table_count = $table_count;
                $info->path = $path;
                $info->admin_index_act = $info->admin_index_act;

                // 설치 유무 체크 (설치는 DB의 설치만 관리)
                if($table_count > $created_table_count) $info->need_install = true;
                else $info->need_install = false;

                // 각 모듈의 module.class.php로 upgrade 유무 체크
                $oDummy = null;
                $oDummy = &getModule($module_name, 'class');
                if($oDummy) $info->need_update = $oDummy->checkUpdate();

                $list[] = $info;
            }
            return $list;
        }

        /**
         * @brief 특정 모듈의 정보와 회원의 정보를 받아서 관리 권한 유무를 판단
         * 회원의 아이디가 해당 모듈의 admin_id에 있으면 true
         * 회원이 속한 그룹이 해당 모듈의 manager 그룹에 있으면 true
         * 이 method는 각 모듈.class.php 에서 isAdmin method에서 사용됨
         **/
        function isModuleAdmin($module_info, $member_info) {
           $user_id = $member_info->user_id;
           $group_list = $member_info->group_list;
           if(!$group_list || !is_array($group_list) || !count($group_list)) return false;

            // 직접 관리자로 선택하였을 경우 확인
            if(is_array($module_info->admin_id) && in_array($user_id, $module_info->admin_id)) return true;

            // 관리자 그룹으로 등록되어 있을 경우 확인
            $manager_group = $module_info->grants['manager'];
            return count(array_intersect(array_keys($group_list), $manager_group));
        }

    }
?>
