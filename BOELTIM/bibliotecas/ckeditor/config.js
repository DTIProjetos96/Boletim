/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Defina o idioma do editor
	config.language = 'pt-br'; // Idioma em português do Brasil

	// Cor de fundo da interface do CKEditor
	config.uiColor = '#f5f5f5';

	// Altura do editor
	config.height = 300;

	// Barra de ferramentas personalizada
	config.toolbar = [
		{ name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'Undo', 'Redo'] },
		{ name: 'editing', items: ['Find', 'Replace', 'SelectAll'] },
		{ name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat'] },
		{ name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Blockquote'] },
		{ name: 'links', items: ['Link', 'Unlink'] },
		{ name: 'insert', items: ['Image', 'Table', 'HorizontalRule'] },
		{ name: 'styles', items: ['Styles', 'Format'] },
		{ name: 'tools', items: ['Maximize'] }
	];

	// Permitir ou proibir elementos HTML específicos
	config.allowedContent = true; // Permitir todo conteúdo por padrão
};
