<?php

namespace App\Admin\Controllers;

use App\Model\MsgModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MsgController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'App\Model\MsgModel';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new MsgModel);

        $grid->column('mid', __('M id'));
        $grid->column('openid', __('Openid'));
        $grid->column('message', __('留言'));
        $grid->column('created_at', __('留言时间'));
      //  $grid->column('updated_at', __('Updated at'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(MsgModel::findOrFail($id));

        $show->field('m_id', __('Mid'));
        $show->field('openid', __('Openid'));
        $show->field('message', __('Message'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new MsgModel);

        $form->text('openid', __('Openid'));
        $form->text('message', __('Message'));

        return $form;
    }
}
