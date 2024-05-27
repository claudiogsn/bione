<?php
/**
 * ServicoList Listing
 * @author  <your name here>
 */
class ServicoList extends TPage
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
        $this->setActiveRecord('Servico');   // defines the active record
        $this->setDefaultOrder('id', 'asc');         // defines the default order
        $this->setLimit(10);
        // $this->setCriteria($criteria) // define a standard filter

        $this->addFilterField('id', '=', 'id'); // filterField, operator, formField
        $this->addFilterField('descricao', 'like', 'descricao'); // filterField, operator, formField
        $this->addFilterField('fornecedor_id', 'like', 'fornecedor_id'); // filterField, operator, formField
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Servico');
        $this->form->setFormTitle('Servico');
        

        // create the form fields
        $id = new TEntry('id');
        $descricao = new TEntry('descricao');
        $fornecedor_id = new TDBUniqueSearch('fornecedor_id', 'communication', 'FinanceiroFornecedor', 'id', 'codigo');


        // add the fields
        $this->form->addFields( [ new TLabel('Id') ], [ $id ] );
        $this->form->addFields( [ new TLabel('Descricao') ], [ $descricao ] );
        $this->form->addFields( [ new TLabel('Fornecedor :') ], [ $fornecedor_id ] );


        // set sizes
        $id->setSize('100%');
        $descricao->setSize('100%');
        $fornecedor_id->setSize('100%');

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );
        
        // add the search form actions
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['ServicoForm', 'onEdit']), 'fa:plus green');
        
        // creates a Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_descricao = new TDataGridColumn('descricao', 'Descricao', 'left');
        $column_valor_servico = new TDataGridColumn('valor_servico', 'Valor Servico', 'right');
        $column_custo_servico = new TDataGridColumn('custo_servico', 'Custo Servico', 'right');
        $column_terceirizado = new TDataGridColumn('terceirizado', 'Terceirizado', 'left');
        $column_fornecedor_id = new TDataGridColumn('fornecedor_id', 'Fornecedor Id', 'right');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_descricao);
        $this->datagrid->addColumn($column_valor_servico);
        $this->datagrid->addColumn($column_custo_servico);
        $this->datagrid->addColumn($column_terceirizado);
        $this->datagrid->addColumn($column_fornecedor_id);

        // define the transformer method over image
        $column_valor_servico->setTransformer( function($value, $object, $row) {
            if (is_numeric($value))
            {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return $value;
        });

        // define the transformer method over image
        $column_custo_servico->setTransformer( function($value, $object, $row) {
            if (is_numeric($value))
            {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return $value;
        });


        
        $action1 = new TDataGridAction(['ServicoForm', 'onEdit'], ['id'=>'{id}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['id'=>'{id}']);
        
        $this->datagrid->addAction($action1, _t('Edit'),   'far:edit blue');
        $this->datagrid->addAction($action2 ,_t('Delete'), 'far:trash-alt red');
        
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
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
}
