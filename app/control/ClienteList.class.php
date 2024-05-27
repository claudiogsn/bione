<?php
/**
 * ClienteList Listing
 */
class ClienteList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;
    
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_search_Cliente');
        $this->form->setFormTitle('Cliente');
        
        // create the form fields
        $nome = new TEntry('nome');
        $cpf_cnpj = new TEntry('cpf_cnpj');

        // add the fields
        $this->form->addFields( [ new TLabel('Nome :') ], [ $nome ] );
        $this->form->addFields( [ new TLabel('CPF/CNPJ :') ], [ $cpf_cnpj ] );

        // set sizes
        $nome->setSize('100%');
        $cpf_cnpj->setSize('100%');

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__ . '_filter_data') );
        
        // add the search form actions
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['ClienteForm', 'onEdit']), 'fa:plus green');
        
        // creates a Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->datatable = 'true';

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_telefone = new TDataGridColumn('telefone', 'Telefone', 'left');
        $column_email = new TDataGridColumn('email', 'Email', 'left');
        $column_cpf_cnpj = new TDataGridColumn('cpf_cnpj', 'CPF/CNPJ', 'left');

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_telefone);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_cpf_cnpj);

        // Adiciona a máscara à coluna cpf_cnpj
        $column_cpf_cnpj->setTransformer(function($value) {
            return $this->applyMask($value);
        });

            // Adiciona a máscara ao número de telefone
        $column_telefone->setTransformer(function($value) {
            return $this->applyPhoneMask($value);
        });

        $action1 = new TDataGridAction(['ClienteForm', 'onEdit'], ['id'=>'{id}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['id'=>'{id}']);
        
        $this->datagrid->addAction($action1, _t('Edit'),   'far:edit blue');
        $this->datagrid->addAction($action2 ,_t('Delete'), 'far:trash-alt red');
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        $container->add(TPanelGroup::pack('', $this->datagrid, $this->pageNavigation));
        
        parent::add($container);
    }

    /**
     * Aplica a máscara ao CPF ou CNPJ
     */
    private function applyMask($value)
    {
        if (strlen($value) == 11) {
            // Aplica máscara de CPF
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $value);
        } elseif (strlen($value) == 14) {
            // Aplica máscara de CNPJ
            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $value);
        }
        return $value;
    }

/**
 * Aplica a máscara ao número de telefone
 */
private function applyPhoneMask($value)
{
    // Remove caracteres não numéricos
    $value = preg_replace('/\D/', '', $value);

    // Verifica o tamanho do número para aplicar a máscara adequada
    if (strlen($value) == 10) {
        // Aplica a máscara para telefone fixo (DD) NNNN-NNNN
        return preg_replace("/(\d{2})(\d{4})(\d{4})/", "($1) $2-$3", $value);
    } elseif (strlen($value) == 11) {
        // Aplica a máscara para celular (DD) 9NNNN-NNNN
        return preg_replace("/(\d{2})(\d{1})(\d{4})(\d{4})/", "($1) $2 $3-$4", $value);
    }

    // Retorna o valor original se não corresponder a nenhuma máscara conhecida
    return $value;
}


    /**
     * Inline record editing
     */
    public function onInlineEdit($param)
    {
        try
        {
            $field = $param['field'];
            $key   = $param['key'];
            $value = $param['value'];
            
            TTransaction::open('communication');
            $object = new Cliente($key);
            $object->{$field} = $value;
            $object->store();
            TTransaction::close();
            
            $this->onReload($param);
            new TMessage('info', "Record Updated");
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Register the filter in the session
     */
    public function onSearch()
    {
        $data = $this->form->getData();
        
        TSession::setValue(__CLASS__.'_filter_nome',   NULL);
        TSession::setValue(__CLASS__.'_filter_cpf_cnpj',   NULL);

        if (isset($data->nome) AND ($data->nome)) {
            $filter = new TFilter('nome', 'like', "%{$data->nome}%");
            TSession::setValue(__CLASS__.'_filter_nome', $filter);
        }

        if (isset($data->cpf_cnpj) AND ($data->cpf_cnpj)) {
            $filter = new TFilter('cpf_cnpj', 'like', "%{$data->cpf_cnpj}%");
            TSession::setValue(__CLASS__.'_filter_cpf_cnpj', $filter);
        }

        $this->form->setData($data);
        
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
            TTransaction::open('communication');
            
            $repository = new TRepository('Cliente');
            $limit = 20;
            $criteria = new TCriteria;
            
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            if (TSession::getValue(__CLASS__.'_filter_nome')) {
                $criteria->add(TSession::getValue(__CLASS__.'_filter_nome'));
            }

            if (TSession::getValue(__CLASS__.'_filter_cpf_cnpj')) {
                $criteria->add(TSession::getValue(__CLASS__.'_filter_cpf_cnpj'));
            }

            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                foreach ($objects as $object)
                {
                    $this->datagrid->addItem($object);
                }
            }
            
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);
            
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
        $action = new TAction([__CLASS__, 'Delete']);
        $action->setParameters($param);
        
        new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
    }
    
    /**
     * Delete a record
     */
    public static function Delete($param)
    {
        try
        {
            $key=$param['key'];
            TTransaction::open('communication');
            $object = new Cliente($key, FALSE);
            $object->delete();
            TTransaction::close();
            
            $pos_action = new TAction([__CLASS__, 'onReload']);
            new TMessage('info', AdiantiCoreTranslator::translate('Record deleted'), $pos_action);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
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
