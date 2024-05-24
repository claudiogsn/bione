<?php
/**
 * ClienteForm Form
 * @author    Claudio Gomes
 */
class ClienteForm extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct($param)
    {
        parent::__construct();

        TScript::create("
            document.addEventListener('DOMContentLoaded', function() {
                var cpfCnpjInput = document.getElementById('cpf_cnpj');
                
                cpfCnpjInput.addEventListener('input', function() {
                    var value = cpfCnpjInput.value.replace(/\D/g, ''); // Remove tudo que não é dígito
                    if (value.length <= 11) {
                        // Máscara de CPF
                        cpfCnpjInput.setAttribute('maxlength', '14');
                        cpfCnpjInput.setAttribute('placeholder', '999.999.999-99');
                        cpfCnpjInput.value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$ } else {
                            // Máscara de CNPJ
                            cpfCnpjInput.setAttribute('maxlength', '18');
                            cpfCnpjInput.setAttribute('placeholder', '99.999.999/9999-99');
                            cpfCnpjInput.value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
                        }
                    });
                });
            ");
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_Cliente');
        $this->form->setFormTitle('Cliente');
        
        // create the form fields
        $id = new THidden('id');
        $cpf_cnpj = new TEntry('cpf_cnpj');
        $nome = new TEntry('nome');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $cep = new TEntry('cep');
        $endereco = new TEntry('endereco');
        $bairro = new TEntry('bairro');
        $cidade = new TEntry('cidade');
        $estado = new TEntry('estado');
        
        // add masks
        //$cpf_cnpj->setMask('999.999.999-99,99.999.999/9999-99');
        $cpf_cnpj->setId('cpf_cnpj');
        $telefone->setMask('(99) 99999-9999');
        
        // add validators
        $email->addValidation('Email', new TEmailValidator);
        $nome->addValidation('Nome', new TRequiredValidator);

        // add the fields
        $this->form->addFields([new TLabel('')], [$id]);
        $this->form->addFields([new TLabel('CPF/CNPJ')], [$cpf_cnpj]);
        $this->form->addFields([new TLabel('Nome')], [$nome]);
        $this->form->addFields([new TLabel('Telefone')], [$telefone]);
        $this->form->addFields([new TLabel('Email')], [$email]);
        $this->form->addFields([new TLabel('CEP')], [$cep]);
        $this->form->addFields([new TLabel('Endereco')], [$endereco]);
        $this->form->addFields([new TLabel('Bairro')], [$bairro]);
        $this->form->addFields([new TLabel('Cidade')], [$cidade]);
        $this->form->addFields([new TLabel('Estado')], [$estado]);

        // set sizes
        $id->setSize('100%');
        $cpf_cnpj->setSize('50%');
        $nome->setSize('50%');
        $telefone->setSize('50%');
        $email->setSize('50%');
        $cep->setSize('50%');
        $endereco->setSize('50%');
        $bairro->setSize('50%');
        $cidade->setSize('50%');
        $estado->setSize('50%');

        // set actions for fields
        $cep->setExitAction(new TAction([$this, 'onExitCep']));
        $cpf_cnpj->setExitAction(new TAction([$this, 'onExitCpfCnpj']));

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
        
        // create the form actions
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction([$this, 'onEdit']), 'fa:eraser red');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        
        parent::add($container);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave($param)
    {
        try
        {
            TTransaction::open('communication'); // open a transaction
            $this->form->validate(); // validate form data
            $data = $this->form->getData(); // get form data as array
            
            $object = new Cliente;  // create an empty object
            $object->fromArray((array) $data); // load the object with data
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
            $this->form->setData($this->form->getData()); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear($param)
    {
        $this->form->clear(TRUE);
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('communication'); // open a transaction
                $object = new Cliente($key); // instantiates the Active Record
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

    /**
     * Event called when the user exits the CEP field
     */
    public static function onExitCep($param)
    {
        $cep = $param['cep'];
        if (!empty($cep)) {
            $url = "https://viacep.com.br/ws/{$cep}/json";
            $content = file_get_contents($url);
            $cep_data = json_decode($content);

            if (isset($cep_data->logradouro)) {
                $data = new stdClass;
                $data->endereco = $cep_data->logradouro;
                $data->bairro = $cep_data->bairro;
                $data->cidade = $cep_data->localidade;
                $data->estado = $cep_data->uf;
                TForm::sendData('form_Cliente', $data);
            } else {
                new TMessage('error', 'CEP não encontrado');
            }
        }
    }

public static function onExitCpfCnpj($param)
{
    $cpf_cnpj = preg_replace('/[^0-9]/', '', $param['cpf_cnpj']);
    if (!empty($cpf_cnpj) && strlen($cpf_cnpj) == 11) { // CPF
        try {
            $url = 'https://api.gw.cellereit.com.br/bg-check/cpf-completo?cpf=' . $cpf_cnpj;
            $headers = [
                'accept: application/json',
                'authorization: ' . 'Bearer eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICIzS1dxVWt4U2pTSDc5OUxnc3cyX0htRFozZDlkVzZoNmtsVGx2Q2t2dkdzIn0.eyJleHAiOjE3MTY1MDg0MDEsImlhdCI6MTcxNjUwODEwMSwianRpIjoiZWEzNjYwOWYtZjcxOS00NTEyLTgxZWMtOWYzNzdmODliZTQ1IiwiaXNzIjoiaHR0cHM6Ly9sb2dpbi5jZWxsZXJlaXQuY29tLmJyL2F1dGgvcmVhbG1zL3BvcnRhbC1jbGllbnRlcy1hcGkiLCJhdWQiOiJhY2NvdW50Iiwic3ViIjoiM2U4OGE3YzktYWJjNS00MjEwLTk3YjgtMTc1M2Y1NjgwZmYyIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoicGRjYS1hcGkiLCJzZXNzaW9uX3N0YXRlIjoiYTgzZDFkMWEtMmQ4NS00MTNlLTgzZjMtNTMwODIyMWM3NjAyIiwiYWNyIjoiMSIsInJlYWxtX2FjY2VzcyI6eyJyb2xlcyI6WyJvZmZsaW5lX2FjY2VzcyIsImRlZmF1bHQtcm9sZXMtcG9ydGFsLWNsaWVudGVzLWFwaSIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJlbWFpbCBwbGFucyBwcm9maWxlIiwic2lkIjoiYTgzZDFkMWEtMmQ4NS00MTNlLTgzZjMtNTMwODIyMWM3NjAyIiwiZW1haWxfdmVyaWZpZWQiOnRydWUsImdyb3VwcyI6WyJhY2NvdW50QWRtaW5zIiwiaW5kaXZpZHVhbHMiXSwiYmlsbGluZ0FjY291bnRJZCI6IjY2NGZkNWM1MWZiZTQ0MjdiMTdlYmYyZiIsInByZWZlcnJlZF91c2VybmFtZSI6ImNsYXVkaW9nc24yQGdtYWlsLmNvbSIsImdpdmVuX25hbWUiOiIiLCJsb2NhbGUiOiJwdC1CUiIsImZhbWlseV9uYW1lIjoiIiwiZW1haWwiOiJjbGF1ZGlvZ3NuMkBnbWFpbC5jb20ifQ.iciOQJIa4TvmNuDI-7Sht3uAGpYesx2Y_hJSEVx5ewxfsp-USqnK6Hg2UwgNzX_qvH7ibJ8soN79yre2IPGEtuP9F8L6EC6Y4WHq8uA4b29yc6I2vSodqjEAF50y6BjSwnIIEuEZD7ojLTDZXLO4-CPEB-_ckJBf6X5hsZC1jEhysYrPi_5v2WsktmgrC7JW5wiRXCwIN5woW5sZFy1j9DfHrs07LVgKbBQeDKYadNTRryECdwn5Lp_DkRPyygM-VyzCMcLq9Hb94MW1TvyuM9SDpD9CsjyolCOvFg5MYTqEuagitwAuUHvqDcY6pqnsNX1SJ9f149cqVGuVTSLwRQ'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            curl_close($ch);

            $cpf_data = json_decode($response);

            if (isset($cpf_data->CadastroPessoaFisica->Nome)) {
                $data = new stdClass;
                $data->nome = $cpf_data->CadastroPessoaFisica->Nome;
                $data->email = $cpf_data->CadastroPessoaFisica->Emails[0]->EnderecoEmail ?? '';
                $data->telefone = $cpf_data->CadastroPessoaFisica->Telefones[0]->TelefoneComDDD ?? '';
                TForm::sendData('form_Cliente', $data);
            } else {
                new TMessage('error', 'CPF não encontrado');
            }
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao consultar CPF: ' . $e->getMessage());
        }
    } elseif (!empty($cpf_cnpj) && strlen($cpf_cnpj) == 14) { // CNPJ
        try {
            $url = "https://www.receitaws.com.br/v1/cnpj/{$cpf_cnpj}";
            $content = file_get_contents($url);
            $cnpj_data = json_decode($content);

            if (isset($cnpj_data->nome)) {
                $data = new stdClass;
                $data->nome = $cnpj_data->nome;
                TForm::sendData('form_Cliente', $data);
            } else {
                new TMessage('error', 'CNPJ não encontrado');
            }
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao consultar CNPJ: ' . $e->getMessage());
        }
    } else {
        new TMessage('error', 'CPF/CNPJ inválido');
    }
}


}
?>
