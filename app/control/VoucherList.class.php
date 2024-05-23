<?php
/**
 * VoucherList Listing
 * @author  <your name here>
 */
class VoucherList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Voucher');
        $this->form->setFormTitle('Voucher');
        

        // create the form fields
        $cpf_cliente = new TEntry('cpf_cliente');
        $codigo = new TEntry('codigo');


        // add the fields
        $this->form->addFields( [ new TLabel('CPF :') ], [ $cpf_cliente ] );
        $this->form->addFields( [ new TLabel('VOUCHER :') ], [ $codigo ] );


        // set sizes
        $cpf_cliente->setSize('40%');
        $codigo->setSize('40%');

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__ . '_filter_data') );
        
        // add the search form actions
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        //$this->form->addActionLink(_t('New'), new TAction(['VoucherForm', 'onEdit']), 'fa:plus green');
        
        // creates a Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_voucher_id = new TDataGridColumn('voucher_id', 'ID', 'left');
        $column_cpf_cliente = new TDataGridColumn('cpf_cliente', 'CPF', 'center');
        $column_codigo = new TDataGridColumn('codigo', 'VOUCHER', 'center');
        $column_campanha_id = new TDataGridColumn('campanha->nome', 'CAMPANHA', 'center');
        $column_data_criacao = new TDataGridColumn('data_criacao', 'DATA CRIAÇÃO', 'center');
        $column_data_uso = new TDataGridColumn('data_uso', 'DATA USO', 'center');
        $column_usuario_uso_id = new TDataGridColumn('usuario_uso_id', 'USER RESGATE', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_voucher_id);
        $this->datagrid->addColumn($column_cpf_cliente);
        $this->datagrid->addColumn($column_codigo);
        $this->datagrid->addColumn($column_campanha_id);
        $this->datagrid->addColumn($column_data_criacao);
        $this->datagrid->addColumn($column_data_uso);
        $this->datagrid->addColumn($column_usuario_uso_id);


        // creates the datagrid column actions
        $column_voucher_id->setAction(new TAction([$this, 'onReload']), ['order' => 'voucher_id']);
        $column_cpf_cliente->setAction(new TAction([$this, 'onReload']), ['order' => 'cpf_cliente']);
        $column_data_criacao->setAction(new TAction([$this, 'onReload']), ['order' => 'data_criacao']);

        // define the transformer method over image
        $column_campanha_id->setTransformer(function($value, $object, $row) {
            return $object->campanha->nome; // retorna o nome da campanha
        });
        $column_data_criacao->setTransformer( function($value, $object, $row) {
            if ($value)
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y H:i:s');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
            return $value;
        });

        // define the transformer method over image
        $column_data_uso->setTransformer( function($value, $object, $row) {
            if ($value)
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y H:i:s');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
            return $value;
        });



        $userGroups = TSession::getValue('usergroupids');

        
        $action1 = new TDataGridAction(['VoucherForm', 'onEdit'], ['voucher_id'=>'{voucher_id}']);
        if (in_array(1, $userGroups)) {
            $action2 = new TDataGridAction([$this, 'onDelete'], ['voucher_id'=>'{voucher_id}']);
        } 
       
        
        $this->datagrid->addAction($action1, _t('Edit'),   'far:edit blue');
        if (in_array(1, $userGroups)) {
            $this->datagrid->addAction($action2, _t('Delete'), 'far:trash-alt red');
        }
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));
        
        parent::add($container);
    }
    
    /**
     * Inline record editing
     * @param $param Array containing:
     *              key: object ID value
     *              field name: object attribute to be updated
     *              value: new attribute content 
     */
    public function onInlineEdit($param)
    {
        try
        {
            // get the parameter $key
            $field = $param['field'];
            $key   = $param['key'];
            $value = $param['value'];
            
            TTransaction::open('communication'); // open a transaction with database
            $object = new Voucher($key); // instantiates the Active Record
            $object->{$field} = $value;
            $object->store(); // update the object in the database
            TTransaction::close(); // close the transaction
            
            $this->onReload($param); // reload the listing
            new TMessage('info', "Record Updated");
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Register the filter in the session
     */
    public function onSearch()
    {
        // get the search form data
        $data = $this->form->getData();
        
        // clear session filters
        TSession::setValue(__CLASS__.'_filter_cpf_cliente',   NULL);
        TSession::setValue(__CLASS__.'_filter_codigo',   NULL);

        if (isset($data->cpf_cliente) AND ($data->cpf_cliente)) {
            $filter = new TFilter('cpf_cliente', 'like', "%{$data->cpf_cliente}%"); // create the filter
            TSession::setValue(__CLASS__.'_filter_cpf_cliente',   $filter); // stores the filter in the session
        }


        if (isset($data->codigo) AND ($data->codigo)) {
            $filter = new TFilter('codigo', 'like', "%{$data->codigo}%"); // create the filter
            TSession::setValue(__CLASS__.'_filter_codigo',   $filter); // stores the filter in the session
        }

        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue(__CLASS__ . '_filter_data', $data);
        
        $param = array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
    }
    
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'communication'
            TTransaction::open('communication');
            
            // creates a repository for Voucher
            $repository = new TRepository('Voucher');
            $limit = 20;
            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'voucher_id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            

            if (TSession::getValue(__CLASS__.'_filter_cpf_cliente')) {
                $criteria->add(TSession::getValue(__CLASS__.'_filter_cpf_cliente')); // add the session filter
            }


            if (TSession::getValue(__CLASS__.'_filter_codigo')) {
                $criteria->add(TSession::getValue(__CLASS__.'_filter_codigo')); // add the session filter
            }

            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $this->datagrid->addItem($object);
                }
            }
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Ask before deletion
     */
    public static function onDelete($param)
    {
        // define the delete action
        $action = new TAction([__CLASS__, 'Delete']);
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
    }
    
    /**
     * Delete a record
     */
    public static function Delete($param)
    {
        try
        {
            $key=$param['key']; // get the parameter $key
            TTransaction::open('communication'); // open a transaction with database
            $object = new Voucher($key, FALSE); // instantiates the Active Record
            $object->delete(); // deletes the object from the database
            TTransaction::close(); // close the transaction
            
            $pos_action = new TAction([__CLASS__, 'onReload']);
            new TMessage('info', AdiantiCoreTranslator::translate('Record deleted'), $pos_action); // success message
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }
}
