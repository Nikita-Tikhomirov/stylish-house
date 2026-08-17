export const buildProductPriceRequestUrl = ({
    width,
    height,
    model,
    control,
    cloth,
    modelId,
    prodTitle,
}) => {
    const params = new URLSearchParams({
        width: width ?? '',
        height: height ?? '',
        model: model ?? '',
        control: control ?? false,
        cloth: cloth ?? '',
        modelId: modelId ?? '',
        prodTitle: prodTitle ?? '',
    });

    return `/sheet-names?${params.toString()}`;
};
