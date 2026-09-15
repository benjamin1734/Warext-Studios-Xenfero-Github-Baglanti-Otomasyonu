<?php

namespace Warext\GitHubSync\Admin\Controller\Traits;

use XF\Mvc\ParameterBag;

trait MappingTemplateActions
{
    public function actionMappings()
    {
        return $this->view('Warext\\GitHubSync:GitHubSync\\Mappings','wgh_mappings',[
            'mappings'=>\XF::finder('Warext\\GitHubSync:Mapping')->order('priority')->fetch(),
            'repositories'=>$this->repositoryChoiceMap()
        ]);
    }

    public function actionMappingEdit(ParameterBag $params)
    {
        $id=(int)($params->mapping_id ?: $this->filter('mapping_id','uint'));
        $m=$id ? $this->assertMappingExists($id) : \XF::em()->create('Warext\\GitHubSync:Mapping');
        if (!$m->exists())
        {
            $m->direction='github_to_xf'; $m->event='release'; $m->event_action='*'; $m->priority=100; $m->enabled=true;
            $m->source_config=[]; $m->target_config=[]; $m->filter_config=[];
        }
        $source=array_replace(['xf_node_id'=>0,'outbound_object'=>'issue','sync_first_post'=>true,'sync_replies'=>true,'sync_thread_state'=>true,'sync_labels'=>false,'field_map'=>[],'pr_head'=>'','pr_base'=>'','pr_draft'=>false,'pr_maintainer_can_modify'=>true,'sync_review_prefix'=>false,'review_prefix_map'=>[],'review_body'=>''],(array)$m->source_config);
        $target=array_replace(['node_id'=>0,'thread_id'=>0,'user_id'=>0,'prefix_id'=>0,'update_first_post'=>false,'update_title'=>false,'delete_policy'=>'mark_deleted','template_id'=>0,'title_template'=>'','message_template'=>'','use_user_mapping'=>false,'sync_remote_labels'=>false,'sync_remote_state'=>false,'label_prefix_map'=>[],'field_map'=>[],'xfrm_enabled'=>false,'xfrm_mode'=>'alongside','xfrm_resource_id'=>0,'xfrm_api_key_ref'=>'','xfrm_api_user_id'=>0,'xfrm_api_bypass'=>false,'xfrm_create_version'=>true,'xfrm_create_update'=>true,'xfrm_download_source'=>'asset','xfrm_asset_pattern'=>'*.zip','xfrm_strip_v'=>true,'xfrm_delete_policy'=>'ignore'],(array)$m->target_config);
        $filter=(array)$m->filter_config;
        $filterText=[];
        foreach (['branches','include_commit','exclude_commit','include_paths','exclude_paths','workflow_statuses','workflow_conclusions'] as $key) $filterText[$key]=implode("\n",(array)($filter[$key]??[]));
        return $this->view('Warext\\GitHubSync:GitHubSync\\MappingEdit','wgh_mapping_edit',[
            'mapping'=>$m,'source'=>$source,'target'=>$target,'filter'=>$filter,'filterText'=>$filterText,
            'labelMapText'=>$this->mapLines((array)$target['label_prefix_map']),
            'inboundFieldMapText'=>$this->stringMapLines((array)$target['field_map']),
            'outboundFieldMapText'=>$this->stringMapLines((array)$source['field_map']),
            'reviewPrefixMapText'=>$this->reviewMapLines((array)$source['review_prefix_map']),
            'repositories'=>\XF::finder('Warext\\GitHubSync:Repository')->order('full_name')->fetch(),
            'templates'=>\XF::finder('Warext\\GitHubSync:Template')->where('active',1)->order('title')->fetch()
        ]);
    }

    public function actionMappingSave(ParameterBag $params)
    {
        $this->assertPostOnly();
        $id=(int)($params->mapping_id ?: $this->filter('mapping_id','uint'));
        $m=$id ? $this->assertMappingExists($id) : \XF::em()->create('Warext\\GitHubSync:Mapping');
        $base=$this->filter(['title'=>'str','repository_id'=>'uint','direction'=>'str','event'=>'str','event_action'=>'str','conflict_strategy'=>'str','priority'=>'int','enabled'=>'bool']);
        $source=$this->filter(['xf_node_id'=>'uint','outbound_object'=>'str','sync_first_post'=>'bool','sync_replies'=>'bool','sync_thread_state'=>'bool','sync_labels'=>'bool','outbound_field_map'=>'str','pr_head'=>'str','pr_base'=>'str','pr_draft'=>'bool','pr_maintainer_can_modify'=>'bool','sync_review_prefix'=>'bool','review_prefix_map'=>'str','review_body'=>'str']);
        $target=$this->filter(['node_id'=>'uint','thread_id'=>'uint','user_id'=>'uint','prefix_id'=>'uint','update_first_post'=>'bool','update_title'=>'bool','delete_policy'=>'str','template_id'=>'uint','title_template'=>'str','message_template'=>'str','use_user_mapping'=>'bool','sync_remote_labels'=>'bool','sync_remote_state'=>'bool','label_prefix_map'=>'str','inbound_field_map'=>'str','xfrm_enabled'=>'bool','xfrm_mode'=>'str','xfrm_resource_id'=>'uint','xfrm_api_key_ref'=>'str','xfrm_api_user_id'=>'uint','xfrm_api_bypass'=>'bool','xfrm_create_version'=>'bool','xfrm_create_update'=>'bool','xfrm_download_source'=>'str','xfrm_asset_pattern'=>'str','xfrm_strip_v'=>'bool','xfrm_delete_policy'=>'str']);
        $rawFilter=$this->filter(['branches'=>'str','include_commit'=>'str','exclude_commit'=>'str','include_paths'=>'str','exclude_paths'=>'str','workflow_statuses'=>'str','workflow_conclusions'=>'str','allow_prerelease'=>'bool','allow_draft'=>'bool']);
        $m->bulkSet($base);
        $m->event=$m->event!==''?$m->event:'*'; $m->event_action=$m->event_action!==''?$m->event_action:'*';
        $m->source_config=['xf_node_id'=>(int)$source['xf_node_id'],'outbound_object'=>$source['outbound_object']?:'issue','sync_first_post'=>(bool)$source['sync_first_post'],'sync_replies'=>(bool)$source['sync_replies'],'sync_thread_state'=>(bool)$source['sync_thread_state'],'sync_labels'=>(bool)$source['sync_labels'],'field_map'=>$this->parseStringMapLines($source['outbound_field_map']),'pr_head'=>$source['pr_head'],'pr_base'=>$source['pr_base'],'pr_draft'=>(bool)$source['pr_draft'],'pr_maintainer_can_modify'=>(bool)$source['pr_maintainer_can_modify'],'sync_review_prefix'=>(bool)$source['sync_review_prefix'],'review_prefix_map'=>$this->parseReviewMapLines($source['review_prefix_map']),'review_body'=>$source['review_body']];
        $m->target_config=['node_id'=>(int)$target['node_id'],'thread_id'=>(int)$target['thread_id'],'user_id'=>(int)$target['user_id'],'prefix_id'=>(int)$target['prefix_id'],'update_first_post'=>(bool)$target['update_first_post'],'update_title'=>(bool)$target['update_title'],'delete_policy'=>$target['delete_policy']?:'mark_deleted','template_id'=>(int)$target['template_id'],'title_template'=>$target['title_template'],'message_template'=>$target['message_template'],'use_user_mapping'=>(bool)$target['use_user_mapping'],'sync_remote_labels'=>(bool)$target['sync_remote_labels'],'sync_remote_state'=>(bool)$target['sync_remote_state'],'label_prefix_map'=>$this->parseMapLines($target['label_prefix_map']),'field_map'=>$this->parseStringMapLines($target['inbound_field_map']),'xfrm_enabled'=>(bool)$target['xfrm_enabled'],'xfrm_mode'=>in_array($target['xfrm_mode'],['alongside','only'],true)?$target['xfrm_mode']:'alongside','xfrm_resource_id'=>(int)$target['xfrm_resource_id'],'xfrm_api_key_ref'=>trim($target['xfrm_api_key_ref']),'xfrm_api_user_id'=>(int)$target['xfrm_api_user_id'],'xfrm_api_bypass'=>(bool)$target['xfrm_api_bypass'],'xfrm_create_version'=>(bool)$target['xfrm_create_version'],'xfrm_create_update'=>(bool)$target['xfrm_create_update'],'xfrm_download_source'=>in_array($target['xfrm_download_source'],['asset','zipball','release_page'],true)?$target['xfrm_download_source']:'asset','xfrm_asset_pattern'=>trim($target['xfrm_asset_pattern'])?:'*.zip','xfrm_strip_v'=>(bool)$target['xfrm_strip_v'],'xfrm_delete_policy'=>in_array($target['xfrm_delete_policy'],['ignore','soft_delete','hard_delete'],true)?$target['xfrm_delete_policy']:'ignore'];
        $m->filter_config=['branches'=>$this->lines($rawFilter['branches']),'include_commit'=>$this->lines($rawFilter['include_commit']),'exclude_commit'=>$this->lines($rawFilter['exclude_commit']),'include_paths'=>$this->lines($rawFilter['include_paths']),'exclude_paths'=>$this->lines($rawFilter['exclude_paths']),'workflow_statuses'=>$this->lines($rawFilter['workflow_statuses']),'workflow_conclusions'=>$this->lines($rawFilter['workflow_conclusions']),'allow_prerelease'=>(bool)$rawFilter['allow_prerelease'],'allow_draft'=>(bool)$rawFilter['allow_draft']];
        if (!$m->exists()) $m->created_date=\XF::$time; $m->updated_date=\XF::$time; $m->save();
        return $this->redirect($this->buildLink('github-sync/mappings'));
    }

    public function actionMappingDelete(ParameterBag $params)
    {
        $id=(int)($params->mapping_id ?: $this->filter('mapping_id','uint')); $m=$this->assertMappingExists($id);
        if ($this->isPost()) { $m->delete(); return $this->redirect($this->buildLink('github-sync/mappings')); }
        return $this->view('Warext\\GitHubSync:GitHubSync\\MappingDelete','wgh_mapping_delete',['mapping'=>$m]);
    }

    public function actionTemplates()
    {
        return $this->view('Warext\\GitHubSync:GitHubSync\\Templates','wgh_templates',['templates'=>\XF::finder('Warext\\GitHubSync:Template')->order('title')->fetch()]);
    }

    public function actionTemplateEdit(ParameterBag $params)
    {
        $id=(int)($params->template_id ?: $this->filter('template_id','uint')); $t=$id?$this->assertTemplateExists($id):\XF::em()->create('Warext\\GitHubSync:Template');
        if (!$t->exists()) { $t->event='*'; $t->active=true; }
        return $this->view('Warext\\GitHubSync:GitHubSync\\TemplateEdit','wgh_template_edit',['template'=>$t]);
    }

    public function actionTemplateSave(ParameterBag $params)
    {
        $this->assertPostOnly(); $id=(int)($params->template_id ?: $this->filter('template_id','uint'));
        $t=$id?$this->assertTemplateExists($id):\XF::em()->create('Warext\\GitHubSync:Template'); $isNew=!$t->exists();
        $t->bulkSet($this->filter(['title'=>'str','event'=>'str','title_template'=>'str','message_template'=>'str','active'=>'bool']));
        $t->event=$t->event!==''?$t->event:'*'; if($isNew)$t->created_date=\XF::$time; $t->updated_date=\XF::$time; $t->save();
        return $this->redirect($this->buildLink('github-sync/templates'));
    }

    public function actionTemplateDelete(ParameterBag $params)
    {
        $id=(int)($params->template_id ?: $this->filter('template_id','uint')); $t=$this->assertTemplateExists($id);
        if($this->isPost()){ $t->delete(); return $this->redirect($this->buildLink('github-sync/templates')); }
        return $this->view('Warext\\GitHubSync:GitHubSync\\TemplateDelete','wgh_template_delete',['template'=>$t]);
    }
}
