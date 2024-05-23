<?php
/**
 * ClienteList Listing
 * @author  <your name here>
 */
class ClienteList extends TPage
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    
    use Adianti\base\AdiantiStandardListTrait;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('communication');            // defines the database
        $this->setActiveRecord('Cliente');   // defines the active record
        $this->setDefaultOrder('cliente_id', 'asc');         // defines the default order
        $this->setLimit(10);
        // $this->setCriteria($criteria) // define a standard filter

        $this->addFilterField('nome', 'like', 'nome'); // filterField, operator, formField
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Cliente');
        $this->form->setFormTitle('Cliente');
        

        // create the form fields
        $nome = new TEntry('nome');


        // add the fields
        $this->form->addFields( [ new TLabel('Nome') ], [ $nome ] );


        // set sizes
        $nome->setSize('100%');

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );
        
        // add the search form actions
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['ClienteForm', 'onEdit']), 'fa:plus green');
        
        // creates a Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_cliente_id = new TDataGridColumn('cliente_id', 'ID', 'center');
        $column_nome = new TDataGridColumn('nome', 'NOME', 'center');
        $column_email = new TDataGridColumn('email', 'EMAIL', 'center');
        $column_telefone = new TDataGridColumn('telefone', 'TELEFONE', 'center');
        $column_cpf = new TDataGridColumn('cpf', 'CPF', 'center');
        $column_data_nascimento = new TDataGridColumn('data_nascimento', 'DATA NASCIMENTO', 'center');


        $column_data_nascimento->setTransformer( function($value, $object, $row) {
            if ($value)
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
            return $value;
        });



        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_cliente_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_telefone);
        $this->datagrid->addColumn($column_cpf);
        $this->datagrid->addColumn($column_data_nascimento);

        $userGroups = TSession::getValue('usergroupids');

        
        $action1 = new TDataGridAction(['ClienteForm', 'onEdit'], ['cliente_id'=>'{cliente_id}']);
        if (in_array(1, $userGroups)) {
            $action2 = new TDataGridAction([$this, 'onDelete'], ['cliente_id'=>'{cliente_id}']);
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
        
        $panel = new TPanelGroup('', 'white');
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);
        
        // header actions
        $dropdown = new TDropDown(_t('Export'), 'fa:list');
        $dropdown->setPullSide('right');
        $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown->addAction( _t('Save as CSV'), new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static'=>'1']), 'fa:table blue' );
        $dropdown->addAction( _t('Save as PDF'), new TAction([$this, 'onExportPDF'], ['register_state' => 'false', 'static'=>'1']), 'far:file-pdf red' );
        $panel->addHeaderWidget( $dropdown );
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
}
