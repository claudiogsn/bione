<?php
/**
 * ServicoForm Form
 * @author  <your name here>
 */
class ServicoForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Servico');
        $this->form->setFormTitle('Servico');
        

        // create the form fields
        $id = new THidden('id');
        $descricao = new TEntry('descricao');
        $valor_servico = new TEntry('valor_servico');
        $custo_servico = new TEntry('custo_servico');
        $terceirizado = new TCombo('terceirizado');
        $fornecedor_id = new TDBUniqueSearch('fornecedor_id', 'communication', 'FinanceiroFornecedor', 'id', 'nome');
        
        
        $options = ['Sim' => 'Sim', 'Nao' => 'Nao']; 
        $terceirizado->addItems($options);
        $terceirizado->enableSearch();
        $valor_servico->setNumericMask(2, ',', '.', true);
        $custo_servico->setNumericMask(2, ',', '.', true);


        // add the fields
        $this->form->addFields( [ new TLabel(' : ') ], [ $id ] );
        $this->form->addFields( [ new TLabel('Descricao : ') ], [ $descricao ] );
        $this->form->addFields( [ new TLabel('Valor Servico : ') ], [ $valor_servico ] );
        $this->form->addFields( [ new TLabel('Custo Servico : ') ], [ $custo_servico ] );
        $this->form->addFields( [ new TLabel('Terceirizado : ') ], [ $terceirizado ] );
        $this->form->addFields( [ new TLabel('Fornecedor: ') ], [ $fornecedor_id ] );

        $descricao->addValidation('Descricao', new TRequiredValidator);
        $valor_servico->addValidation('Valor Servico', new TRequiredValidator);
        $custo_servico->addValidation('Custo Servico', new TRequiredValidator);
        $terceirizado->addValidation('Terceirizado', new TRequiredValidator);
        $fornecedor_id->addValidation('Fornecedor Id', new TRequiredValidator);


        // set sizes
        $id->setSize('50%');
        $descricao->setSize('50%');
        $valor_servico->setSize('50%');
        $custo_servico->setSize('50%');
        $terceirizado->setSize('50%');
        $fornecedor_id->setSize('50%');



        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        /** samples
         $fieldX->addValidation( 'Field X', new TRequiredValidator ); // add validation
         $fieldX->setSize( '100%' ); // set size
         **/
         
        // create the form actions
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'),  new TAction([$this, 'onEdit']), 'fa:eraser red');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('communication'); // open a transaction
            
            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/
            
            $this->form->validate(); // validate form data
            $data = $this->form->getData(); // get form data as array
            
            $object = new Servico;  // create an empty object
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            // get the generated id
            $data->id = $object->id;
            
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('communication'); // open a transaction
                $object = new Servico($key); // instantiates the Active Record
                $this->form->setData($object); // fill the form
                TTransaction::close(); // close the transaction
            }
            else
            {
                $this->form->clear(TRUE);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
}
