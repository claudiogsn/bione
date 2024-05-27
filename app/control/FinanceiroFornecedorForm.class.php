<?php
/**
 * FinanceiroFornecedorForm Form
 * @author  <your name here>
 */
class FinanceiroFornecedorForm extends TPage
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
        $this->form = new BootstrapFormBuilder('form_FinanceiroFornecedor');
        $this->form->setFormTitle('FinanceiroFornecedor');
        

        // create the form fields
        $id = new THidden('id');
        $nome = new TEntry('nome');
        $razao = new TEntry('razao');
        $endereco = new TEntry('endereco');
        $bairro = new TEntry('bairro');
        $cidade = new TEntry('cidade');
        $estado = new TEntry('estado');
        $cep = new TEntry('cep');
        $cpf_cnpj = new TEntry('cpf_cnpj');
        $insc_est = new TEntry('insc_est');
        $insc_mun = new TEntry('insc_mun');
        $email = new TEntry('email');
        $fone = new TEntry('fone');


        // add the fields
        $this->form->addFields( [ new TLabel('') ], [ $id ] );
        $this->form->addFields( [ new TLabel('Nome') ], [ $nome ] );
        $this->form->addFields( [ new TLabel('Razao') ], [ $razao ] );
        $this->form->addFields( [ new TLabel('Endereco') ], [ $endereco ] );
        $this->form->addFields( [ new TLabel('Bairro') ], [ $bairro ] );
        $this->form->addFields( [ new TLabel('Cidade') ], [ $cidade ] );
        $this->form->addFields( [ new TLabel('Estado') ], [ $estado ] );
        $this->form->addFields( [ new TLabel('Cep') ], [ $cep ] );
        $this->form->addFields( [ new TLabel('Cpf Cnpj') ], [ $cpf_cnpj ] );
        $this->form->addFields( [ new TLabel('Insc Est') ], [ $insc_est ] );
        $this->form->addFields( [ new TLabel('Insc Mun') ], [ $insc_mun ] );
        $this->form->addFields( [ new TLabel('Email') ], [ $email ] );
        $this->form->addFields( [ new TLabel('Fone') ], [ $fone ] );



        // set sizes
        $id->setSize('100%');
        $nome->setSize('100%');
        $razao->setSize('100%');
        $endereco->setSize('100%');
        $bairro->setSize('100%');
        $cidade->setSize('100%');
        $estado->setSize('100%');
        $cep->setSize('100%');
        $cpf_cnpj->setSize('100%');
        $insc_est->setSize('100%');
        $insc_mun->setSize('100%');
        $email->setSize('100%');
        $fone->setSize('100%');



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
            
            $object = new FinanceiroFornecedor;  // create an empty object
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
                $object = new FinanceiroFornecedor($key); // instantiates the Active Record
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
