<?php
/**
 * OsForm Master/Detail
 * @author  <your name here>
 */
class OsForm extends TPage
{
    protected $form; // form
    protected $detail_list;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Os');
        $this->form->setFormTitle('Os');
        
        // master fields
        $id = new THidden('id');
        $evento_id = new TDBUniqueSearch('evento_id', 'communication', 'Evento', 'id', 'nome');
        $cliente_id = new TDBUniqueSearch('cliente_id', 'communication', 'Cliente', 'id', 'nome');
        $num_controle = new TEntry('num_controle');
        $data_montagem = new TDate('data_montagem');
        $data_recolhimento = new TDate('data_recolhimento');
        $status = new THidden('status');
        $contato_montagem = new TEntry('contato_montagem');
        $local_montagem = new TEntry('local_montagem');
        $endereco = new TEntry('endereco');

        // detail fields
        $detail_uniqid = new THidden('detail_uniqid');
        $detail_id = new THidden('detail_id');
        $detail_evento_id = new TDBUniqueSearch('detail_evento_id', 'communication', 'Evento', 'id', 'nome');
        $detail_cliente_id = new TDBUniqueSearch('detail_cliente_id', 'communication', 'Cliente', 'id', 'nome');
        $detail_material_id = new TDBUniqueSearch('detail_material_id', 'communication', 'Material', 'id', 'nome');
        $detail_valor = new TEntry('detail_valor');
        $detail_custo = new TEntry('detail_custo');
        $detail_dias_uso = new TEntry('detail_dias_uso');
        $detail_data_inicial = new TDate('detail_data_inicial');
        $detail_status = new THidden('detail_status');

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        // master fields
        $this->form->addFields( [new TLabel('Id')], [$id] );
        $this->form->addFields( [new TLabel('Evento Id')], [$evento_id] );
        $this->form->addFields( [new TLabel('Cliente Id')], [$cliente_id] );
        $this->form->addFields( [new TLabel('Num Controle')], [$num_controle] );
        $this->form->addFields( [new TLabel('Data Montagem')], [$data_montagem] );
        $this->form->addFields( [new TLabel('Data Recolhimento')], [$data_recolhimento] );
        $this->form->addFields( [new TLabel('Status')], [$status] );
        $this->form->addFields( [new TLabel('Contato Montagem')], [$contato_montagem] );
        $this->form->addFields( [new TLabel('Local Montagem')], [$local_montagem] );
        $this->form->addFields( [new TLabel('Endereco')], [$endereco] );
        
        // detail fields
        $this->form->addContent( ['<h4>Itens/Serviços</h4><hr>'] );
        $this->form->addFields( [$detail_uniqid] );
        $this->form->addFields( [$detail_id] );
        
        $this->form->addFields( [new TLabel('Evento Id')], [$detail_evento_id] );
        $this->form->addFields( [new TLabel('Cliente Id')], [$detail_cliente_id] );
        $this->form->addFields( [new TLabel('Material Id')], [$detail_material_id] );
        $this->form->addFields( [new TLabel('Valor')], [$detail_valor] );
        $this->form->addFields( [new TLabel('Custo')], [$detail_custo] );
        $this->form->addFields( [new TLabel('Dias Uso')], [$detail_dias_uso] );
        $this->form->addFields( [new TLabel('Data Inicial')], [$detail_data_inicial] );
        $this->form->addFields( [new TLabel('Status')], [$detail_status] );

        $add = TButton::create('add', [$this, 'onDetailAdd'], 'Register', 'fa:plus-circle green');
        $add->getAction()->setParameter('static','1');
        $this->form->addFields( [], [$add] );
        
        $this->detail_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->detail_list->setId('OsItem_list');
        $this->detail_list->generateHiddenFields();
        $this->detail_list->style = "min-width: 700px; width:100%;margin-bottom: 10px";
        
        // items
        $this->detail_list->addColumn( new TDataGridColumn('uniqid', 'Uniqid', 'center') )->setVisibility(false);
        $this->detail_list->addColumn( new TDataGridColumn('id', 'Id', 'center') )->setVisibility(false);
        $this->detail_list->addColumn( new TDataGridColumn('evento_id', 'Evento Id', 'left', 100) );
        $this->detail_list->addColumn( new TDataGridColumn('cliente_id', 'Cliente Id', 'left', 100) );
        $this->detail_list->addColumn( new TDataGridColumn('material_id', 'Material Id', 'left', 100) );
        $this->detail_list->addColumn( new TDataGridColumn('valor', 'Valor', 'left', 100) );
        $this->detail_list->addColumn( new TDataGridColumn('custo', 'Custo', 'left', 100) );
        $this->detail_list->addColumn( new TDataGridColumn('dias_uso', 'Dias Uso', 'left', 100) );
        $this->detail_list->addColumn( new TDataGridColumn('data_inicial', 'Data Inicial', 'left', 50) );
        $this->detail_list->addColumn( new TDataGridColumn('status', 'Status', 'left', 100) );

        // detail actions
        $action1 = new TDataGridAction([$this, 'onDetailEdit'] );
        $action1->setFields( ['uniqid', '*'] );
        
        $action2 = new TDataGridAction([$this, 'onDetailDelete']);
        $action2->setField('uniqid');
        
        // add the actions to the datagrid
        $this->detail_list->addAction($action1, _t('Edit'), 'fa:edit blue');
        $this->detail_list->addAction($action2, _t('Delete'), 'far:trash-alt red');
        
        $this->detail_list->createModel();
        
        $panel = new TPanelGroup;
        $panel->add($this->detail_list);
        $panel->getBody()->style = 'overflow-x:auto';
        $this->form->addContent( [$panel] );
        
        $this->form->addAction( 'Save',  new TAction([$this, 'onSave'], ['static'=>'1']), 'fa:save green');
        $this->form->addAction( 'Clear', new TAction([$this, 'onClear']), 'fa:eraser red');
        
        // create the page container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        parent::add($container);
    }
    
    
    /**
     * Clear form
     * @param $param URL parameters
     */
    public function onClear($param)
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Add detail item
     * @param $param URL parameters
     */
    public function onDetailAdd( $param )
    {
        try
        {
            $this->form->validate();
            $data = $this->form->getData();
            
            /** validation sample
            if (empty($data->fieldX))
            {
                throw new Exception('The field fieldX is required');
            }
            **/
            
            $uniqid = !empty($data->detail_uniqid) ? $data->detail_uniqid : uniqid();
            
            $grid_data = [];
            $grid_data['uniqid'] = $uniqid;
            $grid_data['id'] = $data->detail_id;
            $grid_data['evento_id'] = $data->detail_evento_id;
            $grid_data['cliente_id'] = $data->detail_cliente_id;
            $grid_data['material_id'] = $data->detail_material_id;
            $grid_data['valor'] = $data->detail_valor;
            $grid_data['custo'] = $data->detail_custo;
            $grid_data['dias_uso'] = $data->detail_dias_uso;
            $grid_data['data_inicial'] = $data->detail_data_inicial;
            $grid_data['status'] = $data->detail_status;
            
            // insert row dynamically
            $row = $this->detail_list->addItem( (object) $grid_data );
            $row->id = $uniqid;
            
            TDataGrid::replaceRowById('OsItem_list', $uniqid, $row);
            
            // clear detail form fields
            $data->detail_uniqid = '';
            $data->detail_id = '';
            $data->detail_evento_id = '';
            $data->detail_cliente_id = '';
            $data->detail_material_id = '';
            $data->detail_valor = '';
            $data->detail_custo = '';
            $data->detail_dias_uso = '';
            $data->detail_data_inicial = '';
            $data->detail_status = '';
            
            // send data, do not fire change/exit events
            TForm::sendData( 'form_Os', $data, false, false );
        }
        catch (Exception $e)
        {
            $this->form->setData( $this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Edit detail item
     * @param $param URL parameters
     */
    public static function onDetailEdit( $param )
    {
        $data = new stdClass;
        $data->detail_uniqid = $param['uniqid'];
        $data->detail_id = $param['id'];
        $data->detail_evento_id = $param['evento_id'];
        $data->detail_cliente_id = $param['cliente_id'];
        $data->detail_material_id = $param['material_id'];
        $data->detail_valor = $param['valor'];
        $data->detail_custo = $param['custo'];
        $data->detail_dias_uso = $param['dias_uso'];
        $data->detail_data_inicial = $param['data_inicial'];
        $data->detail_status = $param['status'];
        
        // send data, do not fire change/exit events
        TForm::sendData( 'form_Os', $data, false, false );
    }
    
    /**
     * Delete detail item
     * @param $param URL parameters
     */
    public static function onDetailDelete( $param )
    {
        // clear detail form fields
        $data = new stdClass;
        $data->detail_uniqid = '';
        $data->detail_id = '';
        $data->detail_evento_id = '';
        $data->detail_cliente_id = '';
        $data->detail_material_id = '';
        $data->detail_valor = '';
        $data->detail_custo = '';
        $data->detail_dias_uso = '';
        $data->detail_data_inicial = '';
        $data->detail_status = '';
        
        // send data, do not fire change/exit events
        TForm::sendData( 'form_Os', $data, false, false );
        
        // remove row
        TDataGrid::removeRowById('OsItem_list', $param['uniqid']);
    }
    
    /**
     * Load Master/Detail data from database to form
     */
    public function onEdit($param)
    {
        try
        {
            TTransaction::open('communication');
            
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                $object = new Os($key);
                $items  = OsItem::where('num_controle', '=', $key)->load();
                
                foreach( $items as $item )
                {
                    $item->uniqid = uniqid();
                    $row = $this->detail_list->addItem( $item );
                    $row->id = $item->uniqid;
                }
                $this->form->setData($object);
                TTransaction::close();
            }
            else
            {
                $this->form->clear(TRUE);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Save the Master/Detail data from form to database
     */
    public function onSave($param)
    {
        try
        {
            // open a transaction with database
            TTransaction::open('communication');
            
            $data = $this->form->getData();
            $this->form->validate();
            
            $master = new Os;
            $master->fromArray( (array) $data);
            $master->store();
            
            OsItem::where('num_controle', '=', $master->id)->delete();
            
            if( $param['OsItem_list_evento_id'] )
            {
                foreach( $param['OsItem_list_evento_id'] as $key => $item_id )
                {
                    $detail = new OsItem;
                    $detail->evento_id  = $param['OsItem_list_evento_id'][$key];
                    $detail->cliente_id  = $param['OsItem_list_cliente_id'][$key];
                    $detail->material_id  = $param['OsItem_list_material_id'][$key];
                    $detail->valor  = $param['OsItem_list_valor'][$key];
                    $detail->custo  = $param['OsItem_list_custo'][$key];
                    $detail->dias_uso  = $param['OsItem_list_dias_uso'][$key];
                    $detail->data_inicial  = $param['OsItem_list_data_inicial'][$key];
                    $detail->status  = $param['OsItem_list_status'][$key];
                    $detail->num_controle = $master->id;
                    $detail->store();
                }
            }
            TTransaction::close(); // close the transaction
            
            TForm::sendData('form_Os', (object) ['id' => $master->id]);
            
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback();
        }
    }
}
