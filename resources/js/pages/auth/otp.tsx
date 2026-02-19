import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

type Props = {
    status?: string;
    email?: string;
};

export default function Otp({ status, email }: Props) {
    return (
        <AuthLayout
            title="Verificacion OTP"
            description="Ingresa el codigo de 6 digitos enviado a tu correo"
        >
            <Head title="Verificacion OTP" />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            {email && (
                <p className="mb-4 text-center text-sm text-muted-foreground">
                    Correo destino: <span className="font-medium">{email}</span>
                </p>
            )}

            <Form action="/otp" method="post" className="space-y-6">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="code">Codigo OTP</Label>
                            <InputOTP
                                id="code"
                                name="code"
                                maxLength={6}
                                inputMode="numeric"
                                pattern={REGEXP_ONLY_DIGITS}
                                autoComplete="one-time-code"
                                autoFocus
                                required
                                containerClassName="justify-center"
                            >
                                <InputOTPGroup className="gap-2">
                                    <InputOTPSlot index={0} className="h-12 w-12 rounded-md border border-input text-base first:rounded-md first:border-l" />
                                    <InputOTPSlot index={1} className="h-12 w-12 rounded-md border border-input text-base first:rounded-md first:border-l" />
                                    <InputOTPSlot index={2} className="h-12 w-12 rounded-md border border-input text-base first:rounded-md first:border-l" />
                                    <InputOTPSlot index={3} className="h-12 w-12 rounded-md border border-input text-base first:rounded-md first:border-l" />
                                    <InputOTPSlot index={4} className="h-12 w-12 rounded-md border border-input text-base first:rounded-md first:border-l" />
                                    <InputOTPSlot index={5} className="h-12 w-12 rounded-md border border-input text-base first:rounded-md first:border-l" />
                                </InputOTPGroup>
                            </InputOTP>
                            <InputError message={errors.code} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="verify-otp-button"
                        >
                            {processing && <Spinner />}
                            Verificar codigo
                        </Button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
