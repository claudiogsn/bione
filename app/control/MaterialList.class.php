<?php
/**
 * MaterialList Listing
 * @author  <your name here>
 */
class MaterialList extends TPage
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
        $this->setActiveRecord('Material');   // defines the active record
        $this->setDefaultOrder('id', 'asc');         // defines the default order
        $this->setLimit(30);
        // $this->setCriteria($criteria) // define a standard filter

 
        $this->addFilterField('nome', 'like', 'nome'); // filterField, operator, formField
        $this->addFilterField('fabricante', '=', 'fabricante'); // filterField, operator, formField
        $this->addFilterField('modelo', 'like', 'modelo'); // filterField, operator, formField
        $this->addFilterField('fornecedor_id', 'like', 'fornecedor_id'); // filterField, operator, formField
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Material');
        $this->form->setFormTitle('Material');
        

        // create the form fields

        $nome = new TEntry('nome');
        $fabricante = new TDBUniqueSearch('fabricante', 'communication', 'Fabricante', 'id', 'nome');
        $modelo = new TEntry('modelo');
        $fornecedor_id = new TDBUniqueSearch('fornecedor_id', 'communication', 'FinanceiroFornecedor', 'id', 'codigo');


        // add the fields

        $this->form->addFields( [ new TLabel('NOME: ') ], [ $nome ] );
        $this->form->addFields( [ new TLabel('FABRICANTE: ') ], [ $fabricante ] );
        $this->form->addFields( [ new TLabel('MODELO: ') ], [ $modelo ] );
        $this->form->addFields( [ new TLabel('FORNECEDOR: ') ], [ $fornecedor_id ] );


        // set sizes

        $nome->setSize('100%');
        $fabricante->setSize('100%');
        $modelo->setSize('100%');
        $fornecedor_id->setSize('100%');

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );
        
        // add the search form actions
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['MaterialForm', 'onEdit']), 'fa:plus green');
        
        // creates a Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'center');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'right');
        $column_modelo = new TDataGridColumn('modelo', 'Modelo', 'left');
        $column_fabricante = new TDataGridColumn('fabricante_id', 'Fabricante', 'left');
        $column_saldo_estoque = new TDataGridColumn('saldo_estoque', 'Saldo Estoque', 'center');
        $column_valor_locacao = new TDataGridColumn('valor_locacao', 'Valor Locacao', 'center');
        $column_sublocado = new TDataGridColumn('sublocado', 'Sublocado', 'center');
        $column_fornecedor_id = new TDataGridColumn('fornecedor_id', 'Fornecedor', 'center');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_modelo);
        $this->datagrid->addColumn($column_fabricante);
        $this->datagrid->addColumn($column_saldo_estoque);
        $this->datagrid->addColumn($column_valor_locacao);
        $this->datagrid->addColumn($column_sublocado);
        $this->datagrid->addColumn($column_fornecedor_id);

        // define the transformer method over image
        $column_valor_locacao->setTransformer( function($value, $object, $row) {
            if (is_numeric($value))
            {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return $value;
        });

        $column_fabricante->setTransformer(function($value, $object, $row) {
            $fabricante = new Fabricante($value);
            if ($fabricante) {
                return $fabricante->nome; 
            } else {
                return ''; 
            }
        });

        $column_fornecedor_id->setTransformer(function($value, $object, $row) {
            $fornecedor = new FinanceiroFornecedor($value);
            if ($fornecedor) {
                return $fornecedor->nome; 
            } else {
                return ''; 
            }
        });


        
        $action1 = new TDataGridAction(['MaterialForm', 'onEdit'], ['id'=>'{id}']);
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
