const baseUrl = window.location.hostname !== 'localhost' ? 'https://binetecnologia.com.br/gestao' : 'http://localhost/bione';



document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('clienteForm');
    const cpfCnpjInput = document.getElementById('cpf');
    const cpfInput = document.getElementById('cpf');
    const cnpjInput = document.getElementById('cnpj');
    const personalInfo = document.getElementById('personalInfo');
    const addressInfo = document.getElementById('addressInfo');
    const cepInput = document.getElementById('cep');

    function toggleCnpjId() {
    if (cpfToggle.checked) {
        cnpjInput.id = 'cpfInput';
    } else {
        cnpjInput.id = 'cnpjInput';
    }
}


    function applyCpfCnpjMask(value) {
        value = value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d)/, "$1.$2");
            value = value.replace(/(\d{3})(\d)/, "$1.$2");
            value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        } else {
            value = value.replace(/^(\d{2})(\d)/, "$1.$2");
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
            value = value.replace(/\.(\d{3})(\d)/, ".$1/$2");
            value = value.replace(/(\d{4})(\d{1,2})$/, "$1-$2");
        }
        return value;
    }

    function cleanCpfCnpj(value) {
        return value.replace(/\D/g, '');
    }

    function fetchCpfData(cpf) {
        return axios.post(`${baseUrl}/api/v1/index.php`, {
            method: 'validateCPF',
            data: { cpf }
        }).then(response => response.data);
    }

    function fetchCnpjData(cnpj) {
        return axios.post(`${baseUrl}/api/v1/index.php`, {
            method: 'validateCNPJ',
            data: { cnpj }
        }).then(response => response.data);
    }

    function fetchCepData(cep) {
        return axios.get(`https://viacep.com.br/ws/${cep}/json`).then(response => response.data);
    }

    function toggleLoading(element, isLoading) {
        if (isLoading) {
            element.classList.add('loading', 'active');
        } else {
            element.classList.remove('loading', 'active');
        }
    }

    cpfCnpjInput.addEventListener('input', async (e) => {
        const value = e.target.value;
        e.target.value = applyCpfCnpjMask(value);

        if (value.length === 14 || value.length === 18) {
            toggleLoading(personalInfo, true);
            try {
                const data = await (value.length === 14 ? fetchCpfData(cleanCpfCnpj(value)) : fetchCnpjData(cleanCpfCnpj(value)));
                if (data.success) {
                    document.getElementById('nome').value = data.data.nome || '';
                    document.getElementById('telefone').value = data.data.telefone || '';
                    document.getElementById('email').value = data.data.email || '';
                } else {
                    alert(data.message || 'Erro ao buscar dados do CPF/CNPJ.');
                }
            } catch (error) {
                alert('Erro ao buscar dados do CPF/CNPJ.');
            } finally {
                toggleLoading(personalInfo, false);
            }
        }
    });

    cpfCnpjInput.addEventListener('blur', async (e) => {
        const value = e.target.value;
        if (value.length === 18) {
            toggleLoading(personalInfo, true);
            try {
                const data = await fetchCnpjData(cleanCpfCnpj(value));
                if (data.success) {
                    document.getElementById('nome').value = data.data.nome || '';
                    document.getElementById('telefone').value = data.data.telefone || '';
                    document.getElementById('email').value = data.data.email || '';
                } else {
                    alert(data.message || 'Erro ao buscar dados do CNPJ.');
                }
            } catch (error) {
                alert('Erro ao buscar dados do CNPJ.');
            } finally {
                toggleLoading(personalInfo, false);
            }
        }
    });

    cepInput.addEventListener('input', async (e) => {
        const cep = e.target.value.replace(/\D/g, '');
        if (cep.length === 8) {
            toggleLoading(addressInfo, true);
            try {
                const data = await fetchCepData(cep);
                if (data.erro) {
                    alert('CEP não encontrado.');
                } else {
                    document.getElementById('endereco').value = data.logradouro || '';
                    document.getElementById('bairro').value = data.bairro || '';
                    document.getElementById('cidade').value = data.localidade || '';
                    document.getElementById('estado').value = data.uf || '';
                }
            } catch (error) {
                alert('Erro ao buscar dados do CEP.');
            } finally {
                toggleLoading(addressInfo, false);
            }
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const data = {
            method: 'createCliente',
            data: {
                nome: formData.get('nome'),
                telefone: formData.get('telefone'),
                email: formData.get('email'),
                cpf_cnpj: cleanCpfCnpj(formData.get('cpf_cnpj')),
                status: 'ativo',
                endereco: `${formData.get('endereco')}, ${formData.get('numero')}`,
                bairro: formData.get('bairro'),
                cidade: formData.get('cidade'),
                estado: formData.get('estado'),
                cep: formData.get('cep').replace(/\D/g, '')
            }
        };

        try {
            const response = await axios.post(`${baseUrl}/api/v1/index.php`, data);
            const result = response.data;
            if (result.success) {
                alert('Cadastro realizado com sucesso!');
                form.reset();
            } else {
                alert(result.error || 'Erro ao realizar o cadastro.');
            }
        } catch (error) {
            alert('Erro ao enviar os dados.');
        }
    });
});
