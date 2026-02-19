import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M6 6H13L27 24V6H34V34H27L13 16V34H6V6Z"
            />
        </svg>
    );
}
