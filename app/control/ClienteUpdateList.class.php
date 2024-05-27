<?php
/**
 * ClienteUpdateList Listing
 * @author  <your name here>
 */
class ClienteUpdateList extends TPage
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $saveButton;
    
    use Adianti\base\AdiantiStandardListTrait;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('communication');            // defines the database
        $this->setActiveRecord('Cliente');   // defines the active record
        $this->setDefaultOrder('id', 'asc');         // defines the default order
        // $this->setCriteria($criteria) // define a standard filter

        $this->addFilterField('nome', 'like', 'nome'); // filterField, operator, formField
        $this->addFilterField('telefone', 'like', 'telefone'); // filterField, operator, formField
        $this->addFilterField('cpf_cnpj', 'like', 'cpf_cnpj'); // filterField, operator, formField
        $this->addFilterField('status', 'like', 'status'); // filterField, operator, formField
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_update_Cliente');
        $this->form->setFormTitle('Cliente');
        

        // create the form fields
        $nome = new TEntry('nome');
        $telefone = new TEntry('telefone');
        $cpf_cnpj = new TEntry('cpf_cnpj');
        $status = new TSelect('status');


        // add the fields
        $this->form->addFields( [ new TLabel('Nome') ], [ $nome ] );
        $this->form->addFields( [ new TLabel('Telefone') ], [ $telefone ] );
        $this->form->addFields( [ new TLabel('Cpf Cnpj') ], [ $cpf_cnpj ] );
        $this->form->addFields( [ new TLabel('Status') ], [ $status ] );


        // set sizes
        $nome->setSize('100%');
        $telefone->setSize('100%');
        $cpf_cnpj->setSize('100%');
        $status->setSize('100%');

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__ . '_filter_data') );
        
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        
        // creates a DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_telefone = new TDataGridColumn('telefone', 'Telefone', 'left');
        $column_email = new TDataGridColumn('email', 'Email', 'left');
        $column_cpf_cnpj = new TDataGridColumn('cpf_cnpj', 'Cpf Cnpj', 'left');
        $column_status = new TDataGridColumn('status', 'Status', 'left');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_telefone);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_cpf_cnpj);
        $this->datagrid->addColumn($column_status);

        
        $column_status->setTransformer( function($value, $object, $row) {
            $widget = new TSelect('status' . '_' . $object->id);
            $widget->setValue( $object->status );
            //$widget->setSize(120);
            $widget->setFormName('form_update_Cliente');
            
            $action = new TAction( [$this, 'onSaveInline'], ['column' => 'status' ] );
            $widget->setChangeAction( $action );
            return $widget;
        });
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // create the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));
        
        parent::add($container);
    }
    
    /**
     * Save the datagrid objects
     */
    public static function onSaveInline($param)
    {
        $name   = $param['_field_name'];
        $value  = $param['_field_value'];
        $column = $param['column'];
        
        $parts  = explode('_', $name);
        $id     = end($parts);
        
        try
        {
            // open transaction
            TTransaction::open('communication');
            
            $object = Cliente::find($id);
            if ($object)
            {
                $object->$column = $value;
                $object->store();
            }
            
            TToast::show('success', 'Record saved', 'bottom center', 'far:check-circle');
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            // show the exception message
            TToast::show('error', $e->getMessage(), 'bottom center', 'fa:exclamation-triangle');
        }
    }
}
