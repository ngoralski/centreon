<?php
/*
 * Copyright 2005-2014 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

namespace Centreon\Core;

/**
 * @author Lionel Assepo <lassepo@merethis.com>
 * @package Centreon
 * @subpackage Core
 */
class Template extends \Smarty
{
    /**
     *
     * @var string 
     */
    private $templateFile;
    
    /**
     *
     * @var array 
     */
    private $cssResources;
    
    /**
     *
     * @var array 
     */
    private $jsTopResources;
    
    /**
     *
     * @var array 
     */
    private $jsBottomResources;
    
    /**
     *
     * @var array 
     */
    private $exclusionList;

    /**
     *
     * @var string
     */
    private $customJs;

    /**
     * 
     * @param string $newTemplateFile
     * @param boolean $enableCaching
     */
    public function __construct($newTemplateFile = '', $enableCaching = 0)
    {
        $this->templateFile = $newTemplateFile;
        $this->caching = $enableCaching;
        
        $this->cssResources = array();
        $this->jsTopResources = array();
        $this->jsBottomResources = array();
        $this->buildExclusionList();
        $this->customJs = "";
        parent::__construct();
        $this->initConfig();
    }
    
    /**
     * 
     */
    public function initConfig()
    {
        $di = \Centreon\Core\Di::getDefault();
        $config = $di->get('config');
        
        // Fixed configuration
        $appPath = realpath(__DIR__ . '/../../../');
        $this->setTemplateDir($appPath . '/views/');
        $this->setConfigDir('');
        $this->addPluginsDir($appPath . '/class/Smarty/');
        
        // Custom configuration
        $this->setCompileDir($config->get('template', 'compile_dir'));
        $this->setCacheDir($config->get('template', 'cache_dir'));
        
        // additional plugin-dir set by user
        $this->addPluginsDir($config->get('template', 'plugins_dir'));
        
        if ($config->get('template', 'debug')) {
            $this->compile_check = true;
            $this->force_compile = true;
            $this->setTemplateDir($config->get('template', 'template_dir'));
        }
    }

    /**
     * Load statics file (css/js)
     *
     * jQuery, bootstrap, font-awesome and centreon
     */
    public function initStaticFiles()
    {
        /* Load css */
        $this->addCss('bootstrap.min.css');
        $this->addCss('font-awesome.min.css');
        $this->addCss('centreon.css');
        $this->addCss('jquery-ui.min.css');
        $this->addCss('jquery.qtip.min.css');
        /* Load javascript */
        $this->addJs('jquery.min.js');
        $this->addJs('jquery-ui.min.js');
        $this->addJs('jquery.qtip.min.js');
        $this->addJs('bootstrap.min.js');
        $this->addJs('jquery.ba-resize.js');
        $this->addJs('centreon.functions.js');
    }
    
    /**
     * @todo Maybe load this list from a config file
     */
    private function buildExclusionList()
    {
        $this->exclusionList = array(
            'cssFileList',
            'jsTopFileList',
            'jsBottomFileList'
        );
    }
    
    /**
     * 
     * {@inheritdoc}
     * @throws \Centreon\Exception If the template file is not defined
     */
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        if ($this->templateFile === "") {
            $this->templateFile = $template;
        }
        $this->loadResources();
        $this->assign('customJs', $this->customJs);
        parent::display($this->templateFile, $cache_id, $compile_id, $parent);
    }
    
    /**
     * 
     * {@inheritdoc}
     * @throws \Centreon\Exception If the template file is not defined
     * @return type
     */
    public function fetch($template = null, $cache_id = null, $compile_id = null,
                            $parent = null, $display = false,
                            $merge_tpl_vars = true, $no_output_filter = false)
    {
        if ($this->templateFile === "") {
            $this->templateFile = $template;
        }
        $this->loadResources();
        $this->assign('customJs', $this->customJs);
        return parent::fetch($this->templateFile, $cache_id, $compile_id,
                                $parent, $display, $merge_tpl_vars,
                                $no_output_filter
        );
    }
    
    /**
     * 
     */
    private function loadResources()
    {
        parent::assign('cssFileList', $this->cssResources);
        parent::assign('jsTopFileList', $this->jsTopResources);
        parent::assign('jsBottomFileList', $this->jsBottomResources);
    }
    
    /**
     * 
     * @param string $fileName $fileName CSS file to add
     * @return \Centreon\Core\Template
     * @throws Exception
     */
    public function addCss($fileName)
    {
        if ($this->isStaticFileExist('css', $fileName)) {
            throw new Exception(_('The given file does not exist'));
        }
        
        if (!in_array($fileName, $this->cssResources)) {
            $this->cssResources[] = $fileName;
        }
        
        return $this;
    }
    
    /**
     * 
     * @param string $fileName Javascript file to add
     * @param string $loadingLocation
     * @return \Centreon\Core\Template
     * @throws Exception
     */
    public function addJs($fileName, $loadingLocation = 'bottom')
    {
        if ($this->isStaticFileExist('js', $fileName)) {
            throw new Exception(_('The given file does not exist'));
        }
        
        switch(strtolower($loadingLocation)) {
            case 'bottom':
            default:
                $jsArray = 'jsBottomResources';
                break;
            case 'top':
                $jsArray = 'jsTopResources';
                break;
        }
        
        if (!in_array($fileName, $this->$jsArray)) {
            $this->{$jsArray}[] = $fileName;
        }
        
        return $this;
    }

    /**
     * 
     * @param string $varName
     * @param mixed $varValue
     * @param boolean $nocache
     * @return \Centreon\Core\Template
     * @throws \Centreon\Core\Exception
     */
    public function assign($varName, $varValue = null, $nocache = false)
    {
        if (in_array($varName, $this->exclusionList)) {
            throw new \Centreon\Core\Exception(_('This variable name is reserved'));
        }
        parent::assign($varName, $varValue, $nocache);
        return $this;
    }
    
    /**
     * 
     * @param string $type
     * @param string $filename
     * @return boolean
     * @throws \Centreon\Core\Exception
     */
    private function isStaticFileExist($type, $filename)
    {
        $di = \Centreon\Core\Di::getDefault();
        $config = $di->get('config');
        $basePath = trim($config->get('global', 'base_path'), '/');
        
        switch(strtolower($type)) {
            case 'css':
                $staticFilePath = trim($config->get('static_file', 'css_path'), '/');
                break;
            case 'js':
                $staticFilePath = trim($config->get('static_file', 'js_path'), '/');
                break;
            case 'img':
                $staticFilePath = trim($config->get('static_file', 'img_path'), '/');
                break;
            default:
                throw new Exception(_('The given filetype is not supported'));
        }
        
        if (!file_exists($basePath.'/'.$staticFilePath.'/'.$filename)) {
            return false;
        }
        
        return true;
    }

    /**
     * Add custom js code
     *
     * @param string $jsStr
     */
    public function addCustomJs($jsStr)
    {
        $this->customJs .= $jsStr . "\n";
    }
}