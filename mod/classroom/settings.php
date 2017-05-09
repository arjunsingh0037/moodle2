<?php  //$Id: settings.php,v 1.1 2008/11/03 05:30:22 fmarier Exp $

    require_once($CFG->dirroot.'/mod/classroom/lib.php');

      $settings->add(new admin_setting_configtext('classroom_fromaddress', get_string('setting:fromaddress_caption', 'classroom'),get_string('setting:fromaddress', 'classroom'), get_string('setting:fromaddressdefault', 'classroom'), "/^((?:[\w\.\-])+\@(?:(?:[a-zA-Z\d\-])+\.)+(?:[a-zA-Z\d]{2,4}))$/",30));
      

      $settings->add(new admin_setting_configtext('classroom_manageraddressformat', get_string('setting:manageraddressformat_caption', 'classroom'),get_string('setting:manageraddressformat', 'classroom'), get_string('setting:manageraddressformatdefault', 'classroom'), PARAM_TEXT));
      

      $settings->add(new admin_setting_configtext('classroom_manageraddressformatreadable', get_string('setting:manageraddressformatreadable_caption', 'classroom'),get_string('setting:manageraddressformatreadable', 'classroom'), get_string('setting:manageraddressformatreadabledefault', 'classroom'), PARAM_NOTAGS));
      

      $settings->add(new admin_setting_configcheckbox('classroom_addchangemanageremail', get_string('setting:addchangemanageremail_caption', 'classroom'),get_string('setting:addchangemanageremail', 'classroom'), get_string('setting:addchangemanageremaildefault', 'classroom'), PARAM_BOOL));
      

      $settings->add(new admin_setting_configcheckbox('classroom_hidecost', get_string('setting:hidecost_caption', 'classroom'),get_string('setting:hidecost', 'classroom'), get_string('setting:hidecostdefault', 'classroom'), PARAM_BOOL));
      

      $settings->add(new admin_setting_configcheckbox('classroom_hidediscount', get_string('setting:hidediscount_caption', 'classroom'),get_string('setting:hidediscount', 'classroom'), get_string('setting:hidediscountdefault', 'classroom'), PARAM_BOOL));
      

      $settings->add(new admin_setting_configcheckbox('classroom_oneemailperday', get_string('setting:oneemailperday_caption', 'classroom'),get_string('setting:oneemailperday', 'classroom'), get_string('setting:oneemailperdaydefault', 'classroom'), PARAM_BOOL));
      

      $settings->add(new admin_setting_configcheckbox('classroom_disableicalcancel', get_string('setting:disableicalcancel_caption', 'classroom'),get_string('setting:disableicalcancel', 'classroom'), get_string('setting:disableicalcanceldefault', 'classroom'), PARAM_BOOL));
      

?>


