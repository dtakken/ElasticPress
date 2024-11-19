/**
 * WordPress dependencies.
 */
import { CheckboxControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginPostStatusInfo as PluginPostStatusInfoLegacy } from '@wordpress/edit-post';
import { PluginPostStatusInfo } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

export default () => {
	const { editPost } = useDispatch('core/editor');

	const { ep_exclude_from_search = false, ...meta } = useSelect(
		(select) => select('core/editor').getEditedPostAttribute('meta') || {},
	);

	const onChange = (ep_exclude_from_search) => {
		editPost({ meta: { ...meta, ep_exclude_from_search } });
	};

	let WrapperElement = null;
	let marginBottomProp = {};

	if (typeof PluginPostStatusInfo !== 'undefined') {
		WrapperElement = PluginPostStatusInfo;
		marginBottomProp = { __nextHasNoMarginBottom: true };
	} else {
		WrapperElement = PluginPostStatusInfoLegacy;
	}

	return (
		<WrapperElement>
			<CheckboxControl
				label={__('Exclude from search results', 'elasticpress')}
				help={__(
					"Excludes this post from the results of your site's search form while ElasticPress is active.",
					'elasticpress',
				)}
				checked={ep_exclude_from_search}
				onChange={onChange}
				{...marginBottomProp}
			/>
		</WrapperElement>
	);
};
