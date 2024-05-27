<?php
/**
 * EventoList Listing
 * @author  <your name here>
 */
class EventoList extends TPage
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
        $this->setActiveRecord('Evento');   // defines the active record
        $this->setDefaultOrder('id', 'asc');         // defines the default order
        $this->setLimit(20);
        // $this->setCriteria($criteria) // define a standard filter

        $this->addFilterField('nome', 'like', 'nome'); // filterField, operator, formField
        $this->addFilterField('cliente_id', '=', 'cliente_id'); // filterField, operator, formField
        $this->addFilterField('data_inicio', '>=', 'data_inicio'); // filterField, operator, formField
        $this->addFilterField('data_fim', '<=', 'data_fim'); // filterField, operator, formField
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Evento');
        $this->form->setFormTitle('Evento');
        

        // create the form fields
        $nome = new TEntry('nome');
        $cliente_id = new TDBUniqueSearch('cliente_id', 'communication', 'Cliente', 'id', 'nome');
        $data_inicio = new TDateTime('data_inicio');
        $data_fim = new TDateTime('data_fim');


        // add the fields
        $this->form->addFields( [ new TLabel('Nome') ], [ $nome ] );
        $this->form->addFields( [ new TLabel('Cliente') ], [ $cliente_id ] );
        $this->form->addFields( [ new TLabel('Data Inicio') ], [ $data_inicio ] );
        $this->form->addFields( [ new TLabel('Data Fim') ], [ $data_fim ] );


        // set sizes
        $nome->setSize('100%');
        $cliente_id->setSize('100%');
        $data_inicio->setSize('100%');
        $data_fim->setSize('100%');

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );
        
        // add the search form actions
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['EventoForm', 'onEdit']), 'fa:plus green');
        
        // creates a Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_cliente_id = new TDataGridColumn('cliente_id', 'Cliente', 'center');
        $column_capacidade = new TDataGridColumn('capacidade', 'Capacidade', 'center');
        $column_data_inicio = new TDataGridColumn('data_inicio', 'Data Inicio', 'left');
        $column_data_fim = new TDataGridColumn('data_fim', 'Data Fim', 'left');
        $column_local = new TDataGridColumn('local', 'Local', 'left');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_cliente_id);
        $this->datagrid->addColumn($column_capacidade);
        $this->datagrid->addColumn($column_data_inicio);
        $this->datagrid->addColumn($column_data_fim);
        $this->datagrid->addColumn($column_local);

        // define the transformer method over image
        $column_data_inicio->setTransformer( function($value, $object, $row) {
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

        // define the transformer method over image
        $column_data_fim->setTransformer( function($value, $object, $row) {
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

    $column_cliente_id->setTransformer(function($value, $object, $row) {
        $client = new Cliente($value);
        if ($client) {
            return $client->nome; 
        } else {
            return ''; 
        }
    });

    // create the actions
    $action1 = new TDataGridAction(['EventoForm', 'onEdit'], ['id'=>'{id}']);
    $action2 = new TDataGridAction([$this, 'onDelete'], ['id'=>'{id}']);

    // add the actions to the datagrid
    $this->datagrid->addAction($action1, _t('Edit'),   'far:edit blue');
    $this->datagrid->addAction($action2 ,_t('Delete'), 'far:trash-alt red');

    // create the datagrid model
    $this->datagrid->createModel();

    // creates the page navigation
    $this->pageNavigation = new TPageNavigation;
    $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

    // create the panel
    $panel = new TPanelGroup('', 'white');
    $panel->add($this->datagrid);
    $panel->addFooter($this->pageNavigation);

    // header actions
    $dropdown = new TDropDown(_t('Export'), 'fa:list');
    $dropdown->setPullSide('right');
    $dropdown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
    $dropdown->addAction( _t('Save as PDF'), new TAction([$this, 'onExportPDF'], ['register_state' => 'false', 'static'=>'1']), 'far:file-pdf red' );
    $panel->addHeaderWidget( $dropdown );

    // vertical box container
    $container = new TVBox;
    $container->style = 'width: 100%';
    // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
    $container->add($this->form);
    $container->add($panel);

    // add the container to the page
    parent::add($container);
}

}

